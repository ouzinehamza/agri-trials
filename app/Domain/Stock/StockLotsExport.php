<?php

namespace App\Domain\Stock;

use App\Domain\Metadata\MetadataService;
use App\Models\StockLot;
use Maatwebsite\Excel\Concerns\{FromArray,WithHeadings};

class StockLotsExport implements FromArray, WithHeadings
{
    private array $customKeys;
    public function __construct() { $this->customKeys=MetadataService::fieldsFor('stock_item')->where('is_system',false)->pluck('key')->all(); }
    public function headings(): array { return ['article','type','reference','unite','seuil_alerte','lot','fournisseur','recu_le','peremption','germination_pct','purete_pct','dernier_test_germination','emplacement','quantite_initiale',...array_map(fn($k)=>"custom.{$k}",$this->customKeys)]; }
    public function array(): array { return StockLot::with(['stockItem','movements'])->get()->map(function($lot){$item=$lot->stockItem;return [$item->name,$item->kind,$item->ref_code,$item->unit,$item->alert_threshold,$lot->code,$lot->supplier_name,$lot->received_on?->toDateString(),$lot->expiry_date?->toDateString(),$lot->germination_pct,$lot->purity_pct,$lot->last_germ_test_on?->toDateString(),$lot->location,(int)$lot->movements->where('type','in')->sum('quantity'),...array_map(fn($k)=>data_get($item->custom_data,$k),$this->customKeys)];})->all(); }
}
