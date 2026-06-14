<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maravel\Models\ModelBase;

class Entity extends ModelBase
{
    protected $fillable = [
        'environment_id',
        'type',
        'name',
        'code',
        'is_active',
        'sort_order',
    ];

    protected $enumCasts = [
        [
            'colum_name' => 'type',
            'additional_column_name' => 'type_fr',
            'choices' => [
                'department' => 'Département',
                'agency' => 'Agence',
            ],
        ],
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
