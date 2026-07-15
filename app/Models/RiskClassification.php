<?php

namespace App\Models;

use Maravel\Models\ModelBase;

class RiskClassification extends ModelBase
{
    /**
     * Cartographie G (colonne) × P (ligne) → code de classification.
     *
     * @var array<int, array<int, string>>
     */
    private const MATRIX_MAP = [
        6 => ['modere', 'modere', 'eleve', 'tres_eleve', 'critique', 'critique'],
        5 => ['modere', 'eleve', 'eleve', 'tres_eleve', 'critique', 'critique'],
        4 => ['faible', 'modere', 'modere', 'eleve', 'tres_eleve', 'tres_eleve'],
        3 => ['faible', 'faible', 'modere', 'modere', 'eleve', 'eleve'],
        2 => ['faible', 'faible', 'faible', 'faible', 'modere', 'modere'],
        1 => ['non_significatif', 'non_significatif', 'non_significatif', 'non_significatif', 'non_significatif', 'non_significatif'],
    ];

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

    public static function forCell(int $gravity, int $probability): ?self
    {
        $code = self::MATRIX_MAP[$probability][$gravity - 1] ?? null;

        if ($code === null) {
            return null;
        }

        return static::query()->where('code', $code)->first();
    }
}
