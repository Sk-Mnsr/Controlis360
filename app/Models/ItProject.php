<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maravel\Models\ModelBase;

class ItProject extends ModelBase
{
    protected $fillable = [
        'priority',
        'name',
        'objective',
        'status',
        'progress_pct',
        'results_benefits',
        'owner',
        'comment',
        'start_date',
        'end_date',
        'delivery_date',
        'environment_id',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'progress_pct' => 'integer',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
