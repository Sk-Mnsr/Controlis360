<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecommendationActionPlan extends BaseModel
{
    protected $fillable = [
        'recommendation_id',
        'user_id',
        'line_number',
        'action_plan',
        'responsible_name',
        'due_date',
        'status',
        'is_waiting',
        'transmission_date',
        'comment',
        'attachment_paths',
    ];

    protected $enumCasts = [
        [
            'colum_name' => 'status',
            'additional_column_name' => 'status_fr',
            'choices' => [
                'non_demarre' => 'Non démarré',
                'en_cours' => 'En cours',
                'en_attente' => 'En attente',
                'en_retard' => 'En retard',
                'cloture' => 'Clôturé',
                'annule' => 'Annulé',
            ],
        ],
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'transmission_date' => 'date',
            'is_waiting' => 'boolean',
            'attachment_paths' => 'array',
        ];
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RecommendationActionPlanComment::class, 'recommendation_action_plan_id');
    }
}
