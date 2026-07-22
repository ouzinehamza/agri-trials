<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use \App\Models\Concerns\Auditable;
    /** Metadata model type used to look up field_definitions. */
    public const MODEL_TYPE = 'supplier';

    /** System (structural) fields stored as real columns; everything else is custom_data. */
    public const SYSTEM_FIELDS = ['name', 'code', 'email', 'phone', 'country'];

    protected $guarded = [];

    protected $casts = [
        'custom_data' => 'array',
    ];
}
