<?php

namespace App\Models;

class Rootstock extends ReferentielModel
{
    protected $table = 'rootstocks';

    public const MODEL_TYPE = 'rootstock';

    public const SYSTEM_FIELDS = ['name'];
}
