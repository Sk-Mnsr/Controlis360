<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Maravel\Models\ModelBase;

class Environment extends ModelBase
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

    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
