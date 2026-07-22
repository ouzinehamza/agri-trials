<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class MeasurementSet extends Model { protected $guarded=[]; protected $casts=['name'=>'array','description'=>'array']; public function measurements(): BelongsToMany { return $this->belongsToMany(Measurement::class,'measurement_set_items')->withPivot(['default_weight','sort_order'])->orderByPivot('sort_order'); } }
