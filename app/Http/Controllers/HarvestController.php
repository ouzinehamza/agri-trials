<?php
namespace App\Http\Controllers;

use App\Domain\Harvests\HarvestAggregationService;
use App\Models\{Harvest,Measurement,MeasurementValue,Trial};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Inertia\{Inertia,Response};
use Symfony\Component\HttpFoundation\StreamedResponse;

class HarvestController extends Controller
{
    public function index(Request $request,Trial $trial,HarvestAggregationService $aggregation): Response
    {
        $this->authorize('view',$trial);$trial->load(['harvests'=>fn($q)=>$q->with(['values.measurement','recorder:id,name'])->orderBy('sequence')]);
        return Inertia::render('Harvests/Index',['trial'=>$trial->only(['id','code','variety','culture','controls','site','season']),'catalog'=>$trial->measurement_snapshot??[],'harvests'=>$trial->harvests->map(fn(Harvest $h)=>['id'=>$h->id,'sequence'=>$h->sequence,'harvested_on'=>$h->harvested_on->toDateString(),'location'=>$h->location,'notes'=>$h->notes,'recorded_by'=>$h->recorder?->name,'values'=>$h->values->map(fn($v)=>['measurement_id'=>$v->measurement_id,'subject_type'=>$v->subject_type,'subject_label'=>$v->subject_label,'value'=>$v->value??data_get($v->value_json,'value')])]),'summary'=>$aggregation->trial($trial),'canCapture'=>$request->user()->can('record',$trial)]);
    }

    public function store(Request $request,Trial $trial): RedirectResponse
    {
        $this->authorize('record',$trial);$data=$request->validate(['harvested_on'=>'required|date','location'=>'nullable|string|max:160','notes'=>'nullable|string|max:1000','values'=>'required|array|min:1','values.*.measurement_id'=>'required|integer|exists:measurements,id','values.*.subject_type'=>'required|in:trial,control','values.*.subject_label'=>'required|string|max:160','values.*.value'=>'nullable']);$allowed=collect($trial->measurement_snapshot??[])->pluck('id')->map(fn($id)=>(int)$id);abort_if(collect($data['values'])->contains(fn($v)=>!$allowed->contains((int)$v['measurement_id'])),422);
        DB::transaction(function()use($trial,$data,$request){$stage=$trial->stageRecords()->whereIn('stage_key',['evaluation','resultat'])->orderBy('sort_order')->first();$harvest=$trial->harvests()->create(['stage_record_id'=>$stage?->id,'sequence'=>(int)$trial->harvests()->max('sequence')+1,'harvested_on'=>$data['harvested_on'],'location'=>$data['location']??null,'notes'=>$data['notes']??null,'recorded_by'=>$request->user()->id,'custom_data'=>[]]);foreach($data['values'] as $value){$measurement=Measurement::findOrFail($value['measurement_id']);$raw=$value['value']??null;if($raw===null||$raw==='')continue;MeasurementValue::create(['trial_id'=>$trial->id,'stage_record_id'=>$stage?->id,'harvest_id'=>$harvest->id,'measurement_id'=>$measurement->id,'subject_type'=>$value['subject_type'],'subject_label'=>$value['subject_label'],'value'=>in_array($measurement->data_type,['number','scale'],true)?$raw:null,'value_json'=>in_array($measurement->data_type,['number','scale'],true)?null:['value'=>$raw]]);}});
        return back()->with('success','Récolte enregistrée.');
    }

    public function export(Request $request,Trial $trial): StreamedResponse
    {
        $this->authorize('view',$trial);$trial->load('harvests.values.measurement');
        return response()->streamDownload(function()use($trial){$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['trial','harvest_sequence','harvested_on','subject_type','subject','measurement_code','measurement','value','unit']);foreach($trial->harvests as $harvest)foreach($harvest->values as $value)fputcsv($out,[$trial->code,$harvest->sequence,$harvest->harvested_on->toDateString(),$value->subject_type,$value->subject_label,$value->measurement?->code,$value->measurement?->name['fr']??$value->measurement?->code,$value->value??data_get($value->value_json,'value'),$value->measurement?->unit]);fclose($out);},"recoltes-{$trial->code}.csv",['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
