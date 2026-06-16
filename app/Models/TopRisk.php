<?php

namespace App\Models;

use Maravel\Models\ModelBase;

class TopRisk extends ModelBase
{
    protected $fillable = [
        'process_name',
        'sub_process_name',
        'major_exceptions',
        'risk_family',
        'gravity',
        'probability',
        'sort_order',
    ];

    public function getGrossRiskAttribute(): ?int
    {
        if ($this->gravity === null || $this->probability === null) {
            return null;
        }

        return $this->gravity * $this->probability;
    }
}
