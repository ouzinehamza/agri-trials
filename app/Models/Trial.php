<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,BelongsToMany,HasMany};
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Trial extends Model implements HasMedia
{
    use InteractsWithMedia;
    use \App\Models\Concerns\Auditable;
    protected $guarded=[];
    protected $casts=['custom_data'=>'array','controls'=>'array','workflow_snapshot'=>'array','measurement_snapshot'=>'array'];
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function decisions(): HasMany { return $this->hasMany(Decision::class)->latest('decided_at'); }
    public function workflowTemplate(): BelongsTo { return $this->belongsTo(WorkflowTemplate::class); }
    public function measurementSet(): BelongsTo { return $this->belongsTo(MeasurementSet::class); }
    public function stageRecords(): HasMany { return $this->hasMany(TrialStageRecord::class); }
    public function measurementValues(): HasMany { return $this->hasMany(MeasurementValue::class); }
    public function notes(): HasMany { return $this->hasMany(TrialNote::class); }
    public function harvests(): HasMany { return $this->hasMany(Harvest::class); }
    /** Users (esp. external partners) explicitly assigned to this trial — SPEC §4 scoping. */
    public function assignees(): BelongsToMany { return $this->belongsToMany(User::class)->withTimestamps(); }

    public function stages(): array
    {
        $records=$this->stageRecords()->orderBy('sort_order')->get();
        if($records->isNotEmpty())return $records->map(fn($record)=>['id'=>$record->id,'key'=>$record->stage_key,'label'=>$record->stage_name['fr']??array_values($record->stage_name??[])[0]??$record->stage_key,'status'=>$record->status==='completed'?'done':($record->status==='active'?'current':'todo'),'config'=>$record->config_snapshot??[],'data'=>$record->data??[]])->all();
        $stages=$this->workflowTemplate?->stages()->get()??collect();
        $keys=$stages->pluck('key')->all();$idx=array_search($this->current_stage,$keys,true);$idx=$idx===false?0:$idx;
        return $stages->values()->map(fn($stage,$i)=>['id'=>null,'key'=>$stage->key,'label'=>$stage->name['fr']??array_values($stage->name)[0]??$stage->key,'status'=>$i<$idx?'done':($i===$idx?'current':'todo'),'config'=>$stage->config??[],'data'=>[]])->all();
    }
}
