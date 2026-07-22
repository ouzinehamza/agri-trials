<?php

namespace App\Models;

class Partner extends ReferentielModel
{
    protected $table = 'partners';

    public const MODEL_TYPE = 'partner';

    public const SYSTEM_FIELDS = ['name'];
}
