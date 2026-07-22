<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable, snapshotted Go/No-Go record (SPEC §7). Rows are append-only — never updated or
 * deleted. Corrections create a new row; the snapshots freeze the score, weights, and numbers
 * the verdict was based on.
 */
class Decision extends Model
{
    use \App\Models\Concerns\Auditable;
    protected $guarded = [];

    protected $casts = [
        'weights_snapshot' => 'array',
        'scorecard_snapshot' => 'array',
        'context_snapshot' => 'array',
        'decided_at' => 'datetime',
    ];

    public const VERDICTS = ['launch', 'reject', 'retrial'];

    public const VERDICT_LABELS = [
        'launch' => 'Lancé en production',
        'reject' => 'Rejeté',
        'retrial' => 'Re-test programmé',
    ];

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function trial(): BelongsTo
    {
        return $this->belongsTo(Trial::class);
    }

    protected static function booted(): void
    {
        static::updating(fn()=>throw new \LogicException('Decision records are immutable.'));
        static::deleting(fn()=>throw new \LogicException('Decision records are immutable.'));
    }
}
