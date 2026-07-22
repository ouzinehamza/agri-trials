<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLot extends Model
{
    protected $guarded = [];
    protected $casts = ['received_on'=>'date','expiry_date'=>'date','last_germ_test_on'=>'date','custom_data'=>'array'];
    public function stockItem(): BelongsTo { return $this->belongsTo(StockItem::class); }
    public function movements(): HasMany { return $this->hasMany(StockMovement::class); }
    public function balance(): int { if($this->relationLoaded('movements')) return (int)$this->movements->where('type','in')->sum('quantity')-(int)$this->movements->where('type','out')->sum('quantity'); return (int)$this->movements()->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) balance")->value('balance'); }
}
