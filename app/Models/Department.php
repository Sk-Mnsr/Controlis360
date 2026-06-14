<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maravel\Models\ModelBase;

class Department extends ModelBase
{
    protected $fillable = [
        'subsidiary_id',
        'name',
        'code',
        'ratio',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ratio' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(Process::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
