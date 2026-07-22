<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class TrialNote extends Model implements HasMedia { use InteractsWithMedia; protected $guarded=[]; public function trial(){return $this->belongsTo(Trial::class);} public function user(){return $this->belongsTo(User::class);} }
