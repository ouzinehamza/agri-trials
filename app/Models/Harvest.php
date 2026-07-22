<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class Harvest extends Model implements HasMedia { use InteractsWithMedia; protected $guarded=[]; protected $casts=['harvested_on'=>'date','custom_data'=>'array']; public function trial(){return $this->belongsTo(Trial::class);} public function stageRecord(){return $this->belongsTo(TrialStageRecord::class);} public function recorder(){return $this->belongsTo(User::class,'recorded_by');} public function values(){return $this->hasMany(MeasurementValue::class);} }
