<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Company-wide audit trail (SPEC §5 AuditLog / §13). Records who changed what & when on the models
 * that carry it. Sensitive/noisy attributes are excluded; only dirty attributes are logged.
 */
trait Auditable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'created_at', 'updated_at', 'custom_data'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
