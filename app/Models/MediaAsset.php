<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;use Spatie\MediaLibrary\HasMedia;use Spatie\MediaLibrary\InteractsWithMedia;
class MediaAsset extends Model implements HasMedia { use InteractsWithMedia; protected $guarded=[];protected $casts=['tags'=>'array'];public function uploader(){return $this->belongsTo(User::class,'created_by');}public function workspace(){return $this->belongsTo(Workspace::class);}public function registerMediaCollections():void{$this->addMediaCollection('file')->singleFile()->useDisk('media');} }
