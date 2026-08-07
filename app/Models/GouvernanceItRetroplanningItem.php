<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GouvernanceItRetroplanningItem extends Model
{
    public const STATUSES = [
        'completed' => 'Completed',
        'in_progress' => 'In progress',
        'en_attente' => 'En attente',
        'not_started' => 'Not Started',
    ];

    public const CATEGORIES = [
        'Legal',
        'Technique',
        'Contrôle',
    ];

    protected $fillable = [
        'activity_id',
        'ensemble_id',
        'category',
        'activity',
        'is_subheader',
        'due_date',
        'status',
        'owner',
        'comments1',
        'comments2',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_subheader' => 'boolean',
        ];
    }

    public function parentActivity(): BelongsTo
    {
        return $this->belongsTo(GouvernanceItActivity::class, 'activity_id');
    }

    public function ensemble(): BelongsTo
    {
        return $this->belongsTo(GouvernanceItRetroplanningEnsemble::class, 'ensemble_id');
    }
}
