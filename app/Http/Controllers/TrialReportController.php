<?php
namespace App\Http\Controllers;
use App\Domain\Reporting\{ReportingService,TrialReportExport};
use App\Models\Trial;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\{Inertia,Response};
use Maatwebsite\Excel\Facades\Excel;
class TrialReportController extends Controller
{
    public function __construct(private ReportingService $reports){}
    public function show(Request $request,Trial $trial):Response{$this->authorize('view',$trial);return Inertia::render('Reports/Trial',$this->present($this->reports->trialReport($trial)));}
    public function excel(Request $request,Trial $trial){$this->authorize('view',$trial);return Excel::download(new TrialReportExport($this->reports->trialReport($trial)),"rapport-{$trial->code}.xlsx");}
    public function pdf(Request $request,Trial $trial){$this->authorize('view',$trial);$report=$this->reports->trialReport($trial);return Pdf::loadView('reports.trial',$report)->setPaper('a4')->download("rapport-{$trial->code}.pdf");}
    private function present(array $r):array{$t=$r['trial'];return ['trial'=>$t->only(['id','code','variety','culture','conduct','supplier','segment','owner','site','season','status','status_tone','controls','custom_data']),'workspace'=>$t->workspace?->name,'stages'=>$t->stages(),'harvests'=>$t->harvests->map(fn($h)=>['sequence'=>$h->sequence,'harvested_on'=>$h->harvested_on->toDateString(),'location'=>$h->location,'values_count'=>$h->values->count()]),'summary'=>$r['summary'],'expenses'=>$r['expenses'],'expense_total'=>$r['expense_total'],'decisions'=>$t->decisions->map(fn($d)=>['id'=>$d->id,'verdict'=>$d->verdict,'verdict_label'=>\App\Models\Decision::VERDICT_LABELS[$d->verdict]??$d->verdict,'score'=>$d->score,'justification'=>$d->justification,'decided_by'=>$d->decider?->name,'decided_at'=>$d->decided_at?->format('d/m/Y H:i')]),'generated_at'=>$r['generated_at']];}
}
