<?php

namespace App\Models;

class Control extends ReferentielModel
{
    protected $table = 'controls';

    public const MODEL_TYPE = 'control';

    public const SYSTEM_FIELDS = ['name'];
}
