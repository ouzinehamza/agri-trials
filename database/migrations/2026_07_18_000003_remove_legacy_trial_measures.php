<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration{
 public function up():void{if(!Schema::hasColumn('trials','measures'))return;foreach(DB::table('trials')->whereNotNull('measures')->get(['id','variety','controls','measures'])as$trial){$rows=json_decode($trial->measures,true)?:[];$controls=json_decode($trial->controls,true)?:[];foreach($rows as$row){$m=DB::table('measurements')->where('code',$row['code']??'')->first();if(!$m)continue;foreach([['trial',$trial->variety,$row['essai']??null],['control',$controls[0]??'Témoin',$row['temoin']??null]]as[$type,$label,$value])if($value!==null)DB::table('measurement_values')->updateOrInsert(['trial_id'=>$trial->id,'stage_record_id'=>null,'measurement_id'=>$m->id,'subject_type'=>$type,'subject_label'=>$label],['value'=>$value,'created_at'=>now(),'updated_at'=>now()]);}}Schema::table('trials',fn(Blueprint $table)=>$table->dropColumn('measures'));}
 public function down():void{Schema::table('trials',fn(Blueprint $table)=>$table->jsonb('measures')->nullable());}
};
