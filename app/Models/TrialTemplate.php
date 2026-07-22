<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TrialTemplate extends Model { protected $guarded=[]; protected $casts=['name'=>'array','config'=>'array','is_archived'=>'boolean']; public function workflow(){return $this->belongsTo(WorkflowTemplate::class,'workflow_template_id');} public function measurementSet(){return $this->belongsTo(MeasurementSet::class);} }
