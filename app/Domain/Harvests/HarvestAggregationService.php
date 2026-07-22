<?php
namespace App\Domain\Harvests;

use App\Models\Trial;
use Illuminate\Support\Collection;

class HarvestAggregationService
{
    public function trial(Trial $trial): array
    {
        $trial->loadMissing('harvests.values.measurement');$catalog=collect($trial->measurement_snapshot??[]);$rows=[];
        foreach($catalog as $measure){$values=$trial->harvests->flatMap->values->where('measurement_id',$measure['id']);$trialValue=$this->aggregate($values->where('subject_type','trial'),$measure['aggregation']);$controls=$values->where('subject_type','control')->groupBy('subject_label');foreach($controls as $label=>$controlValues){$controlValue=$this->aggregate($controlValues,$measure['aggregation']);$rows[]=$this->row($measure,$trialValue,$controlValue,$label);}if($controls->isEmpty())$rows[]=$this->row($measure,$trialValue,null,$trial->controls[0]??'Témoin');}
        return $rows;
    }

    public function variety(Collection $trials): array
    {
        $grouped=collect($trials)->flatMap(fn(Trial $trial)=>$this->trial($trial))->groupBy(fn($r)=>$r['code'].'|'.$r['control_label']);
        return $grouped->map(function($rows){$base=$rows->first();$essai=$rows->pluck('essai')->filter(fn($v)=>$v!==null);$control=$rows->pluck('temoin')->filter(fn($v)=>$v!==null);return $this->row(['code'=>$base['code'],'name'=>['fr'=>$base['label']],'unit'=>$base['unit'],'direction'=>$base['dir'],'weight'=>$base['weight'],'aggregation'=>'mean'],$essai->isEmpty()?null:$essai->avg(),$control->isEmpty()?null:$control->avg(),$base['control_label']);})->values()->all();
    }

    private function aggregate(Collection $rows,string $rule): mixed
    {
        $values=$rows->pluck('value')->filter(fn($v)=>$v!==null)->map(fn($v)=>(float)$v);if($values->isEmpty())return null;
        return round(match($rule){'sum'=>$values->sum(),'min'=>$values->min(),'max'=>$values->max(),'last'=>$values->last(),default=>$values->avg()},4);
    }

    private function row(array $m,mixed $trial,mixed $control,string $label): array
    {
        $direction=$m['direction']??'neutral';$beats=$trial!==null&&$control!==null?match($direction){'up','higher'=> $trial>$control,'down','lower'=>$trial<$control,default=>null}:null;
        return ['code'=>$m['code'],'comparison_key'=>$m['code'].'|'.$label,'label'=>$m['name']['fr']??$m['code'],'unit'=>$m['unit']??'','dir'=>$direction,'aggregation'=>$m['aggregation']??'mean','weight'=>$m['weight']??0,'essai'=>$trial,'temoin'=>$control,'control_label'=>$label,'beats_control'=>$beats];
    }
}
