<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $guarded = [];

    protected $casts = [
        'errors' => 'array',
        'total' => 'integer',
        'imported' => 'integer',
        'failed' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Shape returned to the client for polling/result display. */
    public function toStatusArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total' => $this->total,
            'imported' => $this->imported,
            'failed' => $this->failed,
            'errors' => array_slice($this->errors ?? [], 0, 10),
        ];
    }
}
