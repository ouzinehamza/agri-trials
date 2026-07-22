<?php

namespace App\Domain\Trials;

use App\Domain\Stock\StockService;
use App\Models\{Measurement,MeasurementValue,Trial,TrialStageRecord,TrialTemplate};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowRuntimeService
{
    public function instantiate(Trial $trial, TrialTemplate $template): void
    {
        $workflow=$template->workflow()->with('stages')->firstOrFail();
        $set=$template->measurementSet()->with('measurements')->firstOrFail();
        $workflowSnapshot=$workflow->stages->map(fn($s)=>['key'=>$s->key,'name'=>$s->name,'sort_order'=>$s->sort_order,'config'=>$s->config??[]])->values()->all();
        $measurementSnapshot=$set->measurements->map(fn($m)=>['id'=>$m->id,'code'=>$m->code,'name'=>$m->name,'unit'=>$m->unit,'data_type'=>$m->data_type,'aggregation'=>$m->aggregation,'direction'=>$m->direction,'weight'=>(float)$m->pivot->default_weight])->values()->all();

        DB::transaction(function() use($trial,$template,$workflow,$set,$workflowSnapshot,$measurementSnapshot){
            $first=$workflow->stages->first();
            $trial->update(['trial_template_id'=>$template->id,'workflow_template_id'=>$workflow->id,'measurement_set_id'=>$set->id,'workflow_snapshot'=>$workflowSnapshot,'measurement_snapshot'=>$measurementSnapshot,'current_stage'=>$first?->key??'creation','status'=>$first?->name['fr']??'Création','status_tone'=>'neutral']);
            foreach($workflow->stages as $i=>$stage) TrialStageRecord::create(['trial_id'=>$trial->id,'workflow_stage_id'=>$stage->id,'stage_key'=>$stage->key,'stage_name'=>$stage->name,'sort_order'=>$i,'config_snapshot'=>$stage->config??[],'status'=>$i===0?'active':'pending','started_at'=>$i===0?now():null,'data'=>[]]);
        });
    }

    public function saveStage(Trial $trial, TrialStageRecord $record, array $fields, array $values, string $subjectLabel): void
    {
        abort_unless($record->trial_id===$trial->id,404);
        $config=$record->config_snapshot??[];
        $errors=[];
        foreach($config['fields']??[] as $field){$value=$fields[$field['key']]??null;if(($field['required']??false)&&($value===null||$value===''))$errors["fields.{$field['key']}"]='Ce champ est requis.';}
        $allowed=collect($config['measurement_ids']??[])->map(fn($id)=>(int)$id)->all();
        foreach($values as $value){if(!in_array((int)$value['measurement_id'],$allowed,true))$errors['measurements']='Mesure non autorisée pour cette étape.';}
        if($errors)throw ValidationException::withMessages($errors);

        DB::transaction(function() use($trial,$record,$fields,$values,$subjectLabel){
            $record->update(['data'=>$fields]);
            foreach($values as $value){$measurement=Measurement::findOrFail($value['measurement_id']);$raw=$value['value']??null;MeasurementValue::updateOrCreate(['trial_id'=>$trial->id,'stage_record_id'=>$record->id,'subject_type'=>$value['subject_type'],'subject_label'=>$value['subject_type']==='trial'?$subjectLabel:($value['subject_label']??'Témoin'),'measurement_id'=>$measurement->id],$measurement->data_type==='number'||$measurement->data_type==='scale'?['value'=>$raw,'value_json'=>null]:['value'=>null,'value_json'=>['value'=>$raw]]);}
        });
    }

    public function advance(Trial $trial, TrialStageRecord $record, int $userId): void
    {
        abort_unless($record->trial_id===$trial->id&&$record->status==='active',422);
        $config=$record->config_snapshot??[];$errors=[];
        if($config['required_to_advance']??false){foreach($config['fields']??[] as $field)if(($field['required']??false)&&blank(($record->data??[])[$field['key']]??null))$errors[]=$field['name']['fr']??$field['label']['fr']??$field['key'];foreach($config['measurement_ids']??[] as $id)if(!$trial->measurementValues()->where('stage_record_id',$record->id)->where('measurement_id',$id)->where('subject_type','trial')->exists())$errors[]='mesure #'.$id;}
        if($errors)throw ValidationException::withMessages(['stage'=>'Complétez les données requises : '.implode(', ',$errors)]);
        app(StockService::class)->consumeSowing($trial,$record,$userId);
        DB::transaction(function() use($trial,$record,$userId){$record->update(['status'=>'completed','completed_at'=>now(),'completed_by'=>$userId]);$next=$trial->stageRecords()->where('sort_order','>',$record->sort_order)->orderBy('sort_order')->first();if($next){$next->update(['status'=>'active','started_at'=>now()]);$trial->update(['current_stage'=>$next->stage_key,'status'=>$next->stage_name['fr']??$next->stage_key,'status_tone'=>$next->stage_key==='decision'?'info':'neutral']);}else{$trial->update(['status'=>'Clôturé','status_tone'=>'success']);}});
    }

    public function reopen(Trial $trial, TrialStageRecord $record): void
    {
        abort_unless(in_array($record->status,['completed','active'],true),422);
        DB::transaction(function() use($trial,$record){$trial->stageRecords()->where('sort_order','>',$record->sort_order)->update(['status'=>'pending','started_at'=>null,'completed_at'=>null,'completed_by'=>null]);$record->update(['status'=>'active','completed_at'=>null,'completed_by'=>null,'started_at'=>$record->started_at??now()]);$trial->update(['current_stage'=>$record->stage_key,'status'=>$record->stage_name['fr']??$record->stage_key,'status_tone'=>'neutral']);});
    }
}
