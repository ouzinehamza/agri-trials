<?php

namespace App\Domain\Stock;

use App\Models\{StockItem, StockLot, StockMovement, Trial, TrialStageRecord};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function record(StockItem $item, array $data, int $operatorId): StockMovement
    {
        return DB::transaction(function () use ($item, $data, $operatorId) {
            $lot = StockLot::query()->lockForUpdate()->findOrFail($data['stock_lot_id']);
            abort_unless($lot->stock_item_id === $item->id, 422);
            $balance = $lot->balance();
            if ($data['type'] === 'out' && $balance < (int)$data['quantity']) {
                throw ValidationException::withMessages(['quantity' => "Stock insuffisant dans le lot {$lot->code} ({$balance} disponible)."]);
            }
            return $item->movements()->create([...$data, 'operator_id'=>$operatorId, 'lot_number'=>$lot->code]);
        });
    }

    public function consumeSowing(Trial $trial, TrialStageRecord $record, int $operatorId): void
    {
        if ($record->stage_key !== 'semis') return;
        $allocations = collect(($record->data ?? [])['stock_allocations'] ?? [])->filter(fn($a)=>(int)($a['quantity']??0)>0);
        DB::transaction(function () use ($allocations, $trial, $record, $operatorId) {
            foreach ($allocations as $allocation) {
                $lot = StockLot::query()->lockForUpdate()->findOrFail($allocation['stock_lot_id']);
                if (StockMovement::where('stage_record_id',$record->id)->where('stock_lot_id',$lot->id)->where('reason','semis')->exists()) continue;
                $quantity=(int)$allocation['quantity'];
                if ($lot->balance() < $quantity) throw ValidationException::withMessages(['stage'=>"Stock insuffisant dans le lot {$lot->code}."]);
                StockMovement::create(['stock_item_id'=>$lot->stock_item_id,'stock_lot_id'=>$lot->id,'moved_on'=>now()->toDateString(),'type'=>'out','quantity'=>$quantity,'reason'=>'semis','trial_id'=>$trial->id,'trial_code'=>$trial->code,'operator_id'=>$operatorId,'stage_record_id'=>$record->id,'reference'=>"SEMIS-{$trial->code}",'lot_number'=>$lot->code]);
            }
        });
    }

    public function alerts(StockItem $item): array
    {
        $alerts=[];$balance=$item->currentStock();
        if ($balance <= $item->alert_threshold) $alerts[]=['type'=>$balance<=0?'out':'low','label'=>$balance<=0?'Rupture de stock':'Stock bas'];
        foreach ($item->lots as $lot) {
            if ($lot->balance()<=0) continue;
            if ($lot->expiry_date && $lot->expiry_date->lte(now()->addDays(60))) $alerts[]=['type'=>'expiry','label'=>"Lot {$lot->code} à péremption proche"];
            if (!$lot->last_germ_test_on || $lot->last_germ_test_on->lte(now()->subYear())) $alerts[]=['type'=>'germination','label'=>"Test de germination requis · {$lot->code}"];
        }
        return $alerts;
    }
}
