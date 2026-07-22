<?php

namespace App\Models;

class Segment extends ReferentielModel
{
    protected $table = 'segments';

    public const MODEL_TYPE = 'segment';

    public const SYSTEM_FIELDS = ['name'];
}
