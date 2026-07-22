<?php

namespace App\Http\Controllers;

use App\Domain\Metadata\MetadataService;
use App\Domain\Stock\StockService;
use App\Domain\Stock\StockLotsExport;
use App\Models\{StockItem, StockLot, StockMovement, Trial};
use Illuminate\Http\{RedirectResponse,Request};
use Inertia\{Inertia,Response};
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;

class StockController extends Controller
{
    public function index(Request $request, StockService $service): Response
    {
        $this->authorizeView($request);
        $items=StockItem::with(['lots.movements','movements'])->where('is_archived',false)->orderBy('kind')->orderBy('name')->get()->map(function(StockItem $item) use($service){
            $balance=$item->currentStock();$alerts=$service->alerts($item);
            return ['id'=>$item->id,'kind'=>$item->kind,'name'=>$item->name,'ref_code'=>$item->ref_code,'unit'=>$item->unit,'current_stock'=>$balance,'alert_threshold'=>$item->alert_threshold,'germination_pct'=>$item->germination_pct,'purity_pct'=>$item->purity_pct,'expiry_date'=>$item->expiry_date?->toDateString(),'alerts'=>$alerts,'lots'=>$item->lots->map(fn(StockLot $lot)=>['id'=>$lot->id,'code'=>$lot->code,'supplier_name'=>$lot->supplier_name,'received_on'=>$lot->received_on?->toDateString(),'expiry_date'=>$lot->expiry_date?->toDateString(),'germination_pct'=>$lot->germination_pct,'purity_pct'=>$lot->purity_pct,'last_germ_test_on'=>$lot->last_germ_test_on?->toDateString(),'location'=>$lot->location,'balance'=>$lot->balance()])->values()];
        });
        $movements=StockMovement::query()->leftJoin('stock_items','stock_items.id','=','stock_movements.stock_item_id')->leftJoin('stock_lots','stock_lots.id','=','stock_movements.stock_lot_id')->leftJoin('trials','trials.id','=','stock_movements.trial_id')->leftJoin('users','users.id','=','stock_movements.operator_id')->orderByDesc('stock_movements.moved_on')->orderByDesc('stock_movements.id')->limit(150)->get(['stock_movements.*','stock_items.name as item_name','stock_items.unit as item_unit','stock_lots.code as lot_code','trials.code as trial_code_joined','users.name as operator_name'])->map(fn($m)=>['id'=>$m->id,'moved_on'=>$m->moved_on->toDateString(),'type'=>$m->type,'quantity'=>$m->quantity,'reason'=>$m->reason,'reference'=>$m->reference,'notes'=>$m->notes,'item'=>$m->item_name,'unit'=>$m->item_unit,'lot'=>$m->lot_code,'trial'=>$m->trial_code_joined,'operator'=>$m->operator_name]);
        return Inertia::render('Stock/Index',['items'=>$items,'movements'=>$movements,'trials'=>Trial::orderByDesc('created_at')->get(['id','code','variety as name']),'canManage'=>$this->canManage($request)]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $this->authorizeManage($request,true);
        $data=$request->validate(['name'=>'required|string|max:160','kind'=>'required|in:variety,control','ref_code'=>'nullable|string|max:80','unit'=>'required|string|max:24','alert_threshold'=>'required|integer|min:0','germination_pct'=>'nullable|integer|min:0|max:100','purity_pct'=>'nullable|integer|min:0|max:100','expiry_date'=>'nullable|date','last_germ_test_on'=>'nullable|date']);
        StockItem::create($data); return back()->with('success','Article de stock créé.');
    }

    public function storeLot(Request $request, StockItem $stockItem, StockService $service): RedirectResponse
    {
        $this->authorizeManage($request);
        $data=$request->validate(['code'=>'required|string|max:80','supplier_name'=>'nullable|string|max:160','received_on'=>'nullable|date','expiry_date'=>'nullable|date','germination_pct'=>'nullable|integer|min:0|max:100','purity_pct'=>'nullable|integer|min:0|max:100','last_germ_test_on'=>'nullable|date','location'=>'nullable|string|max:160','initial_quantity'=>'required|integer|min:1']);
        $quantity=$data['initial_quantity'];unset($data['initial_quantity']);
        $lot=$stockItem->lots()->create($data);
        $service->record($stockItem,['stock_lot_id'=>$lot->id,'type'=>'in','quantity'=>$quantity,'moved_on'=>$data['received_on']??now()->toDateString(),'reason'=>'achat','reference'=>'RECEPTION-'.$lot->code,'notes'=>null,'trial_id'=>null,'stage_record_id'=>null],$request->user()->id);
        return back()->with('success','Lot réceptionné.');
    }

    public function storeMovement(Request $request, StockItem $stockItem, StockService $service): RedirectResponse
    {
        $this->authorizeManage($request);
        $data=$request->validate(['stock_lot_id'=>'required|exists:stock_lots,id','type'=>'required|in:in,out','quantity'=>'required|integer|min:1','moved_on'=>'required|date','reason'=>'required|in:achat,allocation,ajustement,transfert,retour,destruction,semis','trial_id'=>'nullable|exists:trials,id','reference'=>'nullable|string|max:120','notes'=>'nullable|string|max:500']);
        $service->record($stockItem,[...$data,'stage_record_id'=>null],$request->user()->id);
        return back()->with('success','Mouvement enregistré.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeView($request);
        return response()->streamDownload(function(){ $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['article','reference','unite','lot','fournisseur','recu_le','peremption','germination_pct','purete_pct','emplacement','stock_disponible']);foreach(StockLot::with('stockItem')->orderBy('stock_item_id')->get() as $lot)fputcsv($out,[$lot->stockItem->name,$lot->stockItem->ref_code,$lot->stockItem->unit,$lot->code,$lot->supplier_name,$lot->received_on?->toDateString(),$lot->expiry_date?->toDateString(),$lot->germination_pct,$lot->purity_pct,$lot->location,$lot->balance()]);fclose($out);},'stock-lots.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function exportXlsx(Request $request)
    {
        $this->authorizeView($request);
        return Excel::download(new StockLotsExport,'stock-lots.xlsx');
    }

    public function import(Request $request, StockService $service): RedirectResponse
    {
        $this->authorizeManage($request,true);
        $request->validate(['file'=>'required|file|max:10240|mimes:xlsx,xls,csv,txt']);
        $rows=Excel::toArray([], $request->file('file'))[0]??[];$header=array_map(fn($v)=>trim((string)$v),array_shift($rows)??[]);$imported=0;$failed=[];
        foreach($rows as $index=>$row){$data=array_combine($header,array_pad($row,count($header),null));try{$validated=validator($data,['article'=>'required|string','type'=>'required|in:variety,control','unite'=>'required|string','lot'=>'required|string','quantite_initiale'=>'required|integer|min:1'])->validate();$custom=[];foreach($data as $key=>$value)if(str_starts_with($key,'custom.')&&$value!==null)$custom[substr($key,7)]=$value;$item=StockItem::updateOrCreate(['name'=>$validated['article'],'kind'=>$validated['type']],['ref_code'=>$data['reference']??null,'unit'=>$validated['unite'],'alert_threshold'=>(int)($data['seuil_alerte']??0),'custom_data'=>$custom]);$lot=$item->lots()->updateOrCreate(['code'=>$validated['lot']],['supplier_name'=>$data['fournisseur']??null,'received_on'=>$data['recu_le']??null,'expiry_date'=>$data['peremption']??null,'germination_pct'=>$data['germination_pct']??null,'purity_pct'=>$data['purete_pct']??null,'last_germ_test_on'=>$data['dernier_test_germination']??null,'location'=>$data['emplacement']??null]);if(!$lot->movements()->exists())$service->record($item,['stock_lot_id'=>$lot->id,'type'=>'in','quantity'=>(int)$validated['quantite_initiale'],'moved_on'=>$data['recu_le']?:now()->toDateString(),'reason'=>'achat','reference'=>'IMPORT-'.$lot->code,'notes'=>'Import tableur','trial_id'=>null,'stage_record_id'=>null],$request->user()->id);$imported++;}catch(\Throwable $e){$failed[]=['line'=>$index+2,'error'=>$e->getMessage()];}}
        return back()->with('import_result',['imported'=>$imported,'failed'=>count($failed),'errors'=>array_slice($failed,0,10)]);
    }

    private function authorizeView(Request $request): void { abort_if($request->user()->role==='partner',403); }
    private function canManage(Request $request): bool { return in_array($request->user()->role,['admin','manager'],true); }
    private function authorizeManage(Request $request,bool $adminOnly=false): void { abort_unless($adminOnly?$request->user()->isAdmin():$this->canManage($request),403); }
}
