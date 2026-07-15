<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Maravel\Models\ModelBase;

class Country extends ModelBase
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function subsidiaries(): HasMany
    {
        return $this->hasMany(Subsidiary::class);
    }
}
