<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class TrialStageRecord extends Model implements HasMedia { use InteractsWithMedia; public $timestamps=false; protected $guarded=[]; protected $casts=['data'=>'array','stage_name'=>'array','config_snapshot'=>'array','started_at'=>'datetime','completed_at'=>'datetime']; public function trial(){return $this->belongsTo(Trial::class);} public function values(){return $this->hasMany(MeasurementValue::class,'stage_record_id');} public function notes(){return $this->hasMany(TrialNote::class,'stage_record_id');} }
