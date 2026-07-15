<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maravel\Models\ModelBase;

class Process extends ModelBase
{
    protected $fillable = [
        'department_id',
        'name',
        'ratio',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'ratio' => 'decimal:2',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subProcesses(): HasMany
    {
        return $this->hasMany(SubProcess::class);
    }
}
