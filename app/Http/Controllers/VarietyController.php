<?php
namespace App\Http\Controllers;
use App\Domain\Decisions\DecisionService;
use App\Domain\Harvests\HarvestAggregationService;
use App\Models\{Decision,Trial};
use Illuminate\Http\{RedirectResponse,Request};
use Inertia\{Inertia,Response};

class VarietyController extends Controller
{
    public function __construct(private HarvestAggregationService $aggregation){}
    public function decision(Request $request,string $variety):Response
    {
        $query=Trial::where('variety',$variety)->orderBy('code');if(!$request->user()->isAdmin())$query->whereIn('workspace_id',$request->user()->workspaceIds());$trials=$query->get();abort_if($trials->isEmpty(),404);
        return Inertia::render('Varieties/Decision',['variety'=>$variety,'measures'=>$this->aggregation->variety($trials),'trials'=>$trials->map(fn($t)=>$t->only(['id','code','site','season','status','status_tone']))->values(),'sites'=>$trials->pluck('site')->filter()->unique()->values(),'seasons'=>$trials->pluck('season')->filter()->unique()->values(),'decisions'=>$this->presentDecisions($variety)]);
    }
    public function storeDecision(Request $request,string $variety):RedirectResponse
    {
        abort_unless($request->user()->isAdmin(),403);$trials=Trial::where('variety',$variety)->get();abort_if($trials->isEmpty(),404);$data=$request->validate(['verdict'=>'required|in:'.implode(',',Decision::VERDICTS),'justification'=>'required|string|min:3','weights'=>'array']);$rows=$this->aggregation->variety($trials);abort_if(collect($rows)->every(fn($r)=>$r['essai']===null||$r['temoin']===null),422,'Aucune récolte comparable.');$card=DecisionService::scorecard($rows,$data['weights']??[]);Decision::create(['level'=>'variety','variety'=>$variety,'verdict'=>$data['verdict'],'score'=>$card['score'],'weights_snapshot'=>$card['weights'],'scorecard_snapshot'=>$card['rows'],'context_snapshot'=>['trial_ids'=>$trials->pluck('id')->all(),'trial_codes'=>$trials->pluck('code')->all(),'sites'=>$trials->pluck('site')->filter()->unique()->values()->all(),'seasons'=>$trials->pluck('season')->filter()->unique()->values()->all(),'harvest_ids'=>$trials->flatMap(fn($t)=>$t->harvests()->pluck('id'))->all()],'justification'=>$data['justification'],'decided_by'=>$request->user()->id,'decided_at'=>now()]);return back()->with('success','Décision variété enregistrée.');
    }
    private function presentDecisions(string $variety):array{return Decision::where('level','variety')->where('variety',$variety)->with('decider:id,name')->latest('decided_at')->get()->map(fn($d)=>['id'=>$d->id,'verdict'=>$d->verdict,'verdict_label'=>Decision::VERDICT_LABELS[$d->verdict]??$d->verdict,'score'=>$d->score,'justification'=>$d->justification,'decided_by'=>$d->decider?->name,'decided_at'=>$d->decided_at?->format('d/m/Y H:i')])->values()->all();}
}
