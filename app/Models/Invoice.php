<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use \App\Models\Concerns\Auditable;
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_on' => 'date',
        'due_on' => 'date',
        'custom_data' => 'array',
    ];

    public const STATUSES = ['draft', 'sent', 'paid', 'overdue'];

    public const STATUS_LABELS = [
        'draft' => 'Brouillon',
        'sent' => 'Envoyée',
        'paid' => 'Payée',
        'overdue' => 'En retard',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
