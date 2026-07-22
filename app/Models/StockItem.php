<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use \App\Models\Concerns\Auditable;
    protected $guarded = [];

    protected $casts = [
        'expiry_date' => 'date',
        'last_germ_test_on' => 'date',
        'custom_data' => 'array',
        'is_archived' => 'boolean',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('moved_on');
    }

    public function lots(): HasMany { return $this->hasMany(StockLot::class); }

    /** Current stock is DERIVED from movements (SPEC invariant: remaining = purchased − consumed). */
    public function currentStock(): int
    {
        if ($this->relationLoaded('movements')) {
            return (int)$this->movements->where('type','in')->sum('quantity') - (int)$this->movements->where('type','out')->sum('quantity');
        }
        return (int) $this->movements()->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) balance")->value('balance');
    }
}
