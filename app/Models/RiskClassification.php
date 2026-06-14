<?php

namespace App\Models;

use Maravel\Models\ModelBase;

class RiskClassification extends ModelBase
{
    protected $fillable = [
        'name',
        'code',
        'min_score',
        'max_score',
        'color',
        'sort_order',
        'description',
    ];

    public static function forScore(int $score): ?self
    {
        return static::query()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('sort_order')
            ->first();
    }
}
