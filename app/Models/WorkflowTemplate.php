<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class WorkflowTemplate extends Model { use \App\Models\Concerns\Auditable; protected $guarded=[]; protected $casts=['name'=>'array','description'=>'array','is_default'=>'boolean','allow_trial_overrides'=>'boolean','is_archived'=>'boolean']; public function stages(): HasMany { return $this->hasMany(WorkflowStage::class)->orderBy('sort_order'); } public function trialTemplates(): HasMany{return $this->hasMany(TrialTemplate::class);} }
