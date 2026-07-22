<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $guarded = [];

    protected $casts = ['moved_on' => 'date'];

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
    public function lot(): BelongsTo { return $this->belongsTo(StockLot::class, 'stock_lot_id'); }
    public function trial(): BelongsTo { return $this->belongsTo(Trial::class); }
    public function operator(): BelongsTo { return $this->belongsTo(User::class, 'operator_id'); }
    public function stageRecord(): BelongsTo { return $this->belongsTo(TrialStageRecord::class); }
}
