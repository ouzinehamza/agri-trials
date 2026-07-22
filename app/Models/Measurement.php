<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Measurement extends Model { protected $guarded = []; protected $casts = ['name'=>'array','description'=>'array','active'=>'boolean']; }
