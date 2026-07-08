<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recommendation extends BaseModel
{
    protected $fillable = [
        'mission_id',
        'reference',
        'name',
        'theme',
        'recommendation_date',
        'risk_level',
        'risk_type',
        'priority',
        'status',
        'responsible_name',
        'due_date',
        'recommendation_label',
        'recommendation_details',
        'comments',
        'attachment_paths',
        'regulator_transmitted_at',
        'regulator_transmitted_by',
    ];

    protected $enumCasts = [
        [
            'colum_name' => 'risk_level',
            'additional_column_name' => 'risk_level_fr',
            'choices' => [
                'faible' => 'Faible',
                'moyen' => 'Moyen',
                'eleve' => 'Élevé',
                'critique' => 'Critique',
            ],
        ],
        [
            'colum_name' => 'priority',
            'additional_column_name' => 'priority_fr',
            'choices' => [
                'basse' => 'Basse',
                'moyenne' => 'Moyenne',
                'haute' => 'Haute',
            ],
        ],
        [
            'colum_name' => 'status',
            'additional_column_name' => 'status_fr',
            'choices' => [
                'emise' => 'Émise',
                'en_cours' => 'En cours',
                'traitee' => 'Traitée',
                'transmis' => 'Transmis',
                'cloturee' => 'Clôturée',
            ],
        ],
    ];

    protected function casts(): array
    {
        return [
            'recommendation_date' => 'date',
            'due_date' => 'date',
            'attachment_paths' => 'array',
            'regulator_transmitted_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    public function followUps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecommendationFollowUp::class)
            ->orderByDesc('commented_at')
            ->orderByDesc('id');
    }

    public function actionPlans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecommendationActionPlan::class)
            ->orderBy('line_number')
            ->orderBy('id');
    }

    public function regulatorTransmitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regulator_transmitted_by');
    }

    public function regulatorComments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecommendationRegulatorComment::class)
            ->orderByDesc('commented_at')
            ->orderByDesc('id');
    }
}
