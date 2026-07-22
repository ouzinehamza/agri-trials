<?php
namespace App\Http\Controllers;

use App\Domain\Decisions\DecisionService;
use App\Domain\Harvests\HarvestAggregationService;
use App\Domain\Trials\WorkflowRuntimeService;
use App\Models\{Decision,StockLot,Trial,TrialStageRecord,TrialTemplate,User,Workspace};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Inertia\{Inertia,Response};

class TrialController extends Controller
{
    public function __construct(private WorkflowRuntimeService $runtime,private HarvestAggregationService $harvestAggregation){}

    public function index(Request $request):Response
    {
        $user=$request->user();$query=Trial::query()->with('workspace:id,name')->orderBy('code');if(!$user->isAdmin()){$query->whereIn('workspace_id',$user->workspaceIds());if($user->isExternalPartner())$query->whereIn('id',$user->assignedTrials()->pluck('trials.id'));}
        $trials=$query->get(['id','code','variety','culture','conduct','supplier','segment','owner','site','season','status','status_tone','current_stage','workspace_id'])->map(fn(Trial $t)=>[...$t->only(['id','code','variety','culture','conduct','supplier','segment','owner','site','season','status','status_tone','current_stage']),'workspace'=>$t->workspace?->name]);
        $workspaceQuery=Workspace::query()->orderBy('name');if(!$user->isAdmin())$workspaceQuery->whereIn('id',$user->workspaceIds());
        return Inertia::render('Trials/Index',['trials'=>$trials,'is_admin'=>$user->isAdmin(),'templates'=>TrialTemplate::where('is_archived',false)->with(['workflow:id,name','measurementSet:id,name'])->get(['id','name','workflow_template_id','measurement_set_id']),'workspaces'=>$workspaceQuery->get(['id','name'])]);
    }

    public function store(Request $request):RedirectResponse
    {
        $d=$request->validate(['code'=>'required|string|max:30|unique:trials,code','trial_template_id'=>'required|exists:trial_templates,id','workspace_id'=>'required|exists:workspaces,id','variety'=>'required|string|max:120','culture'=>'required|string|max:120','conduct'=>'nullable|string|max:120','supplier'=>'nullable|string|max:120','segment'=>'nullable|string|max:180','site'=>'nullable|string|max:120','season'=>'nullable|string|max:30','controls'=>'array','controls.*'=>'string|max:120']);
        $this->authorize('createTrial',Workspace::findOrFail($d['workspace_id']));$template=TrialTemplate::where('is_archived',false)->findOrFail($d['trial_template_id']);
        $trial=DB::transaction(function()use($d,$request,$template){$trial=Trial::create([...$d,'owner'=>$request->user()->name,'controls'=>$d['controls']??[],'custom_data'=>[]]);$this->runtime->instantiate($trial,$template);return $trial;});
        return redirect()->route('trials.show',$trial)->with('success','Essai créé depuis le modèle.');
    }

    public function show(Request $request,Trial $trial):Response{$this->authorize('view',$trial);$lots=StockLot::with('stockItem:id,name,ref_code,unit')->get()->filter(fn($lot)=>$lot->balance()>0)->map(fn($lot)=>['id'=>$lot->id,'label'=>$lot->stockItem->name.' · '.$lot->code,'item'=>$lot->stockItem->name,'unit'=>$lot->stockItem->unit,'available'=>$lot->balance()])->values();$canAssign=$request->user()->can('assign',$trial);return Inertia::render('Trials/Show',['trial'=>$this->present($trial),'can_manage'=>$request->user()->can('record',$trial),'stock_lots'=>$lots,'can_assign'=>$canAssign,'assignees'=>$trial->assignees()->get(['users.id','name','role'])->map(fn($u)=>['id'=>$u->id,'name'=>$u->name,'role'=>User::ROLE_LABELS[$u->role]??$u->role]),'assignable'=>$canAssign?$trial->workspace?->members()->get(['users.id','users.name','users.role','users.is_external'])->map(fn($u)=>['id'=>$u->id,'name'=>$u->name,'role'=>User::ROLE_LABELS[$u->role]??$u->role,'is_external'=>(bool)$u->is_external]):[]]);}

    /** Assign/unassign users (esp. external partners) to a trial — managers of the workspace or admin. */
    public function assign(Request $request,Trial $trial):RedirectResponse
    {
        $this->authorize('assign',$trial);
        $d=$request->validate(['user_ids'=>'array','user_ids.*'=>'integer']);
        // Only users who belong to the trial's workspace may be assigned.
        $memberIds=$trial->workspace?->members()->pluck('users.id')->all()??[];
        $trial->assignees()->sync(array_values(array_intersect($d['user_ids']??[],$memberIds)));
        return back()->with('success','Affectations mises à jour.');
    }
    public function decision(Request $request,Trial $trial):Response{$this->authorize('view',$trial);return Inertia::render('Trials/Decision',['trial'=>$this->present($trial)]);}

    public function saveStage(Request $request,Trial $trial,TrialStageRecord $stageRecord):RedirectResponse
    {
        $this->authorize('record',$trial);$d=$request->validate(['fields'=>'array','measurements'=>'array','measurements.*.measurement_id'=>'required|exists:measurements,id','measurements.*.subject_type'=>'required|in:trial,control','measurements.*.subject_label'=>'nullable|string|max:120','measurements.*.value'=>'nullable']);$this->runtime->saveStage($trial,$stageRecord,$d['fields']??[],$d['measurements']??[],$trial->variety);return back()->with('success','Données de l’étape enregistrées.');
    }
    public function advanceStage(Request $request,Trial $trial,TrialStageRecord $stageRecord):RedirectResponse{$this->authorize('record',$trial);$this->authorizeCompletionRole($request,$trial,$stageRecord);$this->runtime->advance($trial,$stageRecord,$request->user()->id);return back()->with('success','Étape terminée.');}
    public function reopenStage(Request $request,Trial $trial,TrialStageRecord $stageRecord):RedirectResponse{$this->authorize('reopen',$trial);$this->runtime->reopen($trial,$stageRecord);return back()->with('success','Étape rouverte.');}
    public function storeNote(Request $request,Trial $trial):RedirectResponse{$this->authorize('record',$trial);$d=$request->validate(['body'=>'required|string|max:2000','stage_record_id'=>'nullable|exists:trial_stage_records,id']);if(isset($d['stage_record_id']))abort_unless($trial->stageRecords()->whereKey($d['stage_record_id'])->exists(),422);$trial->notes()->create([...$d,'user_id'=>$request->user()->id]);return back()->with('success','Note ajoutée.');}

    public function storeDecision(Request $request,Trial $trial):RedirectResponse
    {
        $this->authorize('decide',$trial);$d=$request->validate(['verdict'=>'required|in:'.implode(',',Decision::VERDICTS),'justification'=>'required|string|min:3','weights'=>'array']);$rows=$this->measurementRows($trial);abort_if(collect($rows)->every(fn($r)=>$r['essai']===null||$r['temoin']===null),422,'Aucune récolte comparable.');$card=DecisionService::scorecard($rows,$d['weights']??[]);
        $trial->decisions()->create(['level'=>'trial','verdict'=>$d['verdict'],'score'=>$card['score'],'weights_snapshot'=>$card['weights'],'scorecard_snapshot'=>$card['rows'],'context_snapshot'=>['trial_id'=>$trial->id,'trial_code'=>$trial->code,'site'=>$trial->site,'season'=>$trial->season,'harvest_ids'=>$trial->harvests()->pluck('id')->all()],'justification'=>$d['justification'],'decided_by'=>$request->user()->id,'decided_at'=>now()]);
        [$status,$tone]=match($d['verdict']){'launch'=>['Lancé','success'],'reject'=>['Rejeté','danger'],default=>['Re-test','warning']};$last=$trial->stageRecords()->orderByDesc('sort_order')->first();$trial->update(['status'=>$status,'status_tone'=>$tone,'current_stage'=>$last?->stage_key??$trial->current_stage]);return redirect()->route('trials.show',$trial)->with('success','Décision enregistrée.');
    }

    private function present(Trial $trial):array
    {
        $trial->load(['stageRecords.values.measurement','notes.user:id,name']);$stages=collect($trial->stages())->map(function($stage)use($trial){$record=$trial->stageRecords->firstWhere('id',$stage['id']);$stage['values']=$record?->values->map(fn($v)=>['measurement_id'=>$v->measurement_id,'code'=>$v->measurement?->code,'subject_type'=>$v->subject_type,'subject_label'=>$v->subject_label,'value'=>$v->value??data_get($v->value_json,'value')])->values()->all()??[];return $stage;})->all();
        return [...$trial->only(['id','code','variety','culture','conduct','supplier','segment','owner','site','season','status','status_tone','current_stage','controls']),'stages'=>$stages,'measures'=>$this->measurementRows($trial),'workflow'=>$trial->workflow_snapshot,'measurement_catalog'=>$trial->measurement_snapshot??[],'notes'=>$trial->notes->map(fn($n)=>['id'=>$n->id,'body'=>$n->body,'user'=>$n->user?->name,'stage_record_id'=>$n->stage_record_id,'created_at'=>$n->created_at->format('d/m/Y H:i')]),'decisions'=>$trial->decisions()->with('decider:id,name')->get()->map(fn(Decision $d)=>['id'=>$d->id,'verdict'=>$d->verdict,'verdict_label'=>Decision::VERDICT_LABELS[$d->verdict]??$d->verdict,'score'=>$d->score,'justification'=>$d->justification,'decided_by'=>$d->decider?->name,'decided_at'=>$d->decided_at?->format('d/m/Y H:i'),'scorecard'=>$d->scorecard_snapshot])];
    }
    private function measurementRows(Trial $trial):array{return $this->harvestAggregation->trial($trial);}
    /** Stage-specific gate: the stage's configured completion_roles (in addition to the `record` policy). */
    private function authorizeCompletionRole(Request $r,Trial $trial,TrialStageRecord $record):void{$roles=$record->config_snapshot['completion_roles']??[];if(!$roles)return;$role=$r->user()->isAdmin()?'admin':optional($r->user()->workspaces()->where('workspaces.id',$trial->workspace_id)->first())->pivot?->role;abort_unless(in_array($role,$roles,true),403);}
}
