<?php
namespace Database\Seeders;
use App\Models\{Harvest,Measurement,MeasurementValue,Trial,User};
use Illuminate\Database\Seeder;

class HarvestSeeder extends Seeder
{
    public function run():void
    {
        $admin=User::where('role','admin')->first();$base=['YLD'=>[3.8,3.4],'BRIX'=>[13.2,12.1],'PMF'=>[1240,1170],'NBF'=>[4.1,4.4],'FRM'=>[7.6,7.0],'CONS'=>[18,15]];
        foreach(Trial::with('stageRecords')->get() as $trial){$trial->harvests()->each(fn($h)=>$h->delete());$stage=$trial->stageRecords->firstWhere('stage_key','evaluation');foreach([1,2] as $seq){$harvest=Harvest::create(['trial_id'=>$trial->id,'stage_record_id'=>$stage?->id,'sequence'=>$seq,'harvested_on'=>now()->subDays(28-$seq*7),'location'=>$trial->site,'notes'=>$seq===1?'Première récolte de démonstration':null,'recorded_by'=>$admin?->id,'custom_data'=>[]]);foreach($base as $code=>[$trialValue,$controlValue]){$measurement=Measurement::where('code',$code)->first();if(!$measurement)continue;$offset=($trial->id%3)*.05+($seq-1)*.1;foreach([['trial',$trial->variety,$trialValue+$offset],['control',$trial->controls[0]??'Témoin',$controlValue+($seq-1)*.05]] as[$type,$label,$value])MeasurementValue::create(['trial_id'=>$trial->id,'stage_record_id'=>$stage?->id,'harvest_id'=>$harvest->id,'subject_type'=>$type,'subject_label'=>$label,'measurement_id'=>$measurement->id,'value'=>$value]);}}}
    }
}
