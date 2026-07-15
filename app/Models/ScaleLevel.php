<?php

namespace App\Models;

use Maravel\Models\ModelBase;

class ScaleLevel extends ModelBase
{
    protected $fillable = [
        'type',
        'level',
        'label',
        'qualification',
        'description',
        'maturity_label',
    ];
}
