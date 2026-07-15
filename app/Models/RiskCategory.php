<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Maravel\Models\ModelBase;

class RiskCategory extends ModelBase
{
    protected $fillable = [
        'number',
        'name',
        'description',
        'sort_order',
    ];

    public function families(): HasMany
    {
        return $this->hasMany(RiskFamily::class);
    }
}
