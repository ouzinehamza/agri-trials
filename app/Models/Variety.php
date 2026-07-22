<?php

namespace App\Models;

class Variety extends ReferentielModel
{
    protected $table = 'varieties';

    public const MODEL_TYPE = 'variety';

    public const SYSTEM_FIELDS = ['name'];
}
