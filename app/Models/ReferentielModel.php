<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for simple metadata-driven référentiels: a `name` system column + `custom_data` JSONB for
 * every admin-defined field. Concrete models set $table, MODEL_TYPE, and SYSTEM_FIELDS.
 */
abstract class ReferentielModel extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = ['custom_data' => 'array'];

    public const MODEL_TYPE = '';

    public const SYSTEM_FIELDS = ['name'];
}
