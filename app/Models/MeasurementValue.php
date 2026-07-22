<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MeasurementValue extends Model { protected $guarded=[]; protected $casts=['value'=>'decimal:4','value_json'=>'array']; public function measurement(){ return $this->belongsTo(Measurement::class); } public function harvest(){return $this->belongsTo(Harvest::class);} }
