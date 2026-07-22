<?php
namespace App\Domain\Reporting;

use App\Domain\Harvests\HarvestAggregationService;
use App\Domain\Stock\StockService;
use App\Models\{Decision,Expense,StockItem,Trial,User,WorkflowTemplate,Workspace};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportingService
{
    public function __construct(private HarvestAggregationService $aggregation,private StockService $stock){}

    public function trialQuery(User $user,array $filters=[]): Builder
    {
        $q=Trial::query();if(!$user->isAdmin())$q->whereIn('workspace_id',$user->workspaceIds());
        if($filters['workspace_id']??null)$q->where('workspace_id',$filters['workspace_id']);if($filters['site']??null)$q->where('site',$filters['site']);if($filters['season']??null)$q->where('season',$filters['season']);return $q;
    }

    public function dashboard(Request $request): array
    {
        $filters=$request->only(['workspace_id','site','season']);$base=$this->trialQuery($request->user(),$filters);$trials=(clone $base)->with('harvests')->get();$ids=$trials->pluck('id');$codes=$trials->pluck('code');$varieties=$trials->pluck('variety')->unique();$decisions=Decision::where(fn($q)=>$q->whereIn('trial_id',$ids)->orWhere(fn($q)=>$q->where('level','variety')->whereIn('variety',$varieties)))->get();
        $workflow=WorkflowTemplate::with('stages')->orderByDesc('is_default')->first();$counts=$trials->countBy('current_stage');$pipeline=($workflow?->stages??collect())->map(fn($s)=>['key'=>$s->key,'label'=>$s->name['fr']??$s->key,'n'=>(int)($counts[$s->key]??0)])->values();
        $harvestTrend=$trials->flatMap->harvests->groupBy(fn($h)=>$h->harvested_on->format('Y-m'))->map(fn($rows,$month)=>['month'=>$month,'count'=>$rows->count()])->sortBy('month')->values();
        $performance=collect($trials)->flatMap(fn($t)=>$this->aggregation->trial($t))->filter(fn($r)=>$r['essai']!==null&&$r['temoin']!==null)->groupBy('code')->map(function($rows){$first=$rows->first();$dev=$rows->avg(fn($r)=>$r['temoin']==0?0:(($r['essai']-$r['temoin'])/$r['temoin'])*100);return ['code'=>$first['code'],'label'=>$first['label'],'unit'=>$first['unit'],'trial'=>round($rows->avg('essai'),2),'control'=>round($rows->avg('temoin'),2),'deviation'=>round($dev,1)];})->values();
        $expenses=Expense::whereIn('trial_code',$codes)->get();$stockAlerts=collect();if(in_array($request->user()->role,['admin','manager'],true))$stockAlerts=StockItem::with(['lots.movements','movements'])->where('is_archived',false)->get()->flatMap(fn($item)=>collect($this->stock->alerts($item))->map(fn($a)=>[...$a,'item'=>$item->name]));
        return ['stats'=>['active'=>$trials->where('current_stage','!=','cloture')->count(),'in_decision'=>$trials->where('current_stage','decision')->count(),'harvests'=>$trials->sum(fn($t)=>$t->harvests->count()),'decisions'=>$decisions->count(),'launch_rate'=>$decisions->count()?round($decisions->where('verdict','launch')->count()/$decisions->count()*100):0,'expenses'=>(float)$expenses->sum('amount'),'stock_alerts'=>$stockAlerts->count()],'pipeline'=>$pipeline,'harvestTrend'=>$harvestTrend,'performance'=>$performance,'arbitrate'=>$trials->whereIn('current_stage',['evaluation','decision'])->sortByDesc(fn($t)=>$t->current_stage==='decision')->take(6)->map(fn($t)=>$t->only(['id','code','variety','culture','site','status','status_tone']))->values(),'recent'=>$decisions->sortByDesc('decided_at')->take(6)->map(fn($d)=>['id'=>$d->id,'variety'=>$d->level==='variety'?$d->variety:$trials->firstWhere('id',$d->trial_id)?->variety,'level'=>$d->level,'verdict'=>$d->verdict,'verdict_label'=>Decision::VERDICT_LABELS[$d->verdict]??$d->verdict,'score'=>$d->score,'decided_at'=>$d->decided_at?->format('d/m/Y')])->values(),'stockAlerts'=>$stockAlerts->take(6)->values(),'filters'=>$filters,'options'=>['workspaces'=>($request->user()->isAdmin()?Workspace::query():$request->user()->workspaces())->orderBy('name')->get(['workspaces.id','workspaces.name']),'sites'=>$this->trialQuery($request->user())->whereNotNull('site')->distinct()->orderBy('site')->pluck('site'),'seasons'=>$this->trialQuery($request->user())->whereNotNull('season')->distinct()->orderByDesc('season')->pluck('season')]];
    }

    public function trialReport(Trial $trial): array
    {
        $trial->load(['workspace:id,name','harvests.values.measurement','harvests.recorder:id,name','stageRecords','notes.user:id,name','decisions.decider:id,name']);$summary=$this->aggregation->trial($trial);$expenses=Expense::where('trial_code',$trial->code)->get();return ['trial'=>$trial,'summary'=>$summary,'expenses'=>$expenses,'expense_total'=>(float)$expenses->sum('amount'),'generated_at'=>now()->format('d/m/Y H:i')];
    }
}
