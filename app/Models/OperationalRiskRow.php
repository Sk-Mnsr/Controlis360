<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maravel\Models\ModelBase;

class OperationalRiskRow extends ModelBase
{
    protected $fillable = [
        'entity_id',
        'process_number',
        'process_name',
        'ratio',
        'sub_process_name',
        'major_exceptions',
        'correlated_risks',
        'risk_family',
        'gravity',
        'probability',
        'control_description',
        'control_exists',
        'control_owner',
        'control_effectiveness',
        'residual_gravity',
        'residual_probability',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'control_exists' => 'boolean',
            'ratio' => 'decimal:2',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function getGrossRiskAttribute(): ?int
    {
        if ($this->gravity === null || $this->probability === null) {
            return null;
        }

        return $this->gravity * $this->probability;
    }

    public function getResidualRiskAttribute(): ?int
    {
        if ($this->residual_gravity === null || $this->residual_probability === null) {
            return null;
        }

        return $this->residual_gravity * $this->residual_probability;
    }
}
