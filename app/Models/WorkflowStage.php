<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkflowStage extends Model { public $timestamps=false; protected $guarded=[]; protected $casts=['name'=>'array','config'=>'array']; public function workflow(){return $this->belongsTo(WorkflowTemplate::class,'workflow_template_id');} }
