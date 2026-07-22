<?php
namespace App\Domain\Reporting;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
class TrialReportExport implements WithMultipleSheets
{
    public function __construct(private array $report){}
    public function sheets():array{$t=$this->report['trial'];$summary=$this->report['summary'];return [new ArrayReportSheet('Synthèse',['Champ','Valeur'],[['Code',$t->code],['Variété',$t->variety],['Culture',$t->culture],['Site',$t->site],['Saison',$t->season],['Statut',$t->status],['Charges',$this->report['expense_total'].' MAD']]),new ArrayReportSheet('Mesures',['Code','Mesure','Agrégation','Essai','Témoin','Contrôle','Écart %','Meilleur'],array_map(fn($r)=>[$r['code'],$r['label'],$r['aggregation'],$r['essai'],$r['temoin'],$r['control_label'],$r['temoin']?round(($r['essai']-$r['temoin'])/$r['temoin']*100,1):0,$r['beats_control']===true?'Oui':($r['beats_control']===false?'Non':'Neutre')],$summary)),new ArrayReportSheet('Récoltes',['N°','Date','Lieu','Mesure','Sujet','Valeur','Unité'],$t->harvests->flatMap(fn($h)=>$h->values->map(fn($v)=>[$h->sequence,$h->harvested_on->toDateString(),$h->location,$v->measurement?->name['fr']??$v->measurement?->code,$v->subject_label,$v->value??data_get($v->value_json,'value'),$v->measurement?->unit]))->all()),new ArrayReportSheet('Décisions',['Date','Verdict','Score','Justification','Décideur'],$t->decisions->map(fn($d)=>[$d->decided_at?->format('Y-m-d'),$d->verdict,$d->score,$d->justification,$d->decider?->name])->all())];}
}
