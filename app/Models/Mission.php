<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mission extends BaseModel
{
    protected $fillable = [
        'reference',
        'mission_type',
        'created_by',
        'auditor',
        'issue_date',
        'start_date',
        'end_date',
        'report_reference',
        'report_attachment_paths',
        'status',
        'comments',
    ];

    protected $enumCasts = [
        [
            'colum_name' => 'mission_type',
            'additional_column_name' => 'mission_type_fr',
            'choices' => [
                'audit_interne' => 'Audit Interne',
                'audit_externe' => 'Audit Externe',
                'controle_permanent' => 'Contrôle Permanent',
                'inspection' => 'Inspection',
                'cac' => 'CAC',
                'regulateur' => 'Régulateur',
            ],
        ],
        [
            'colum_name' => 'status',
            'additional_column_name' => 'status_fr',
            'choices' => [
                'ouvert' => 'Ouvert',
                'ferme' => 'Fermé',
            ],
        ],
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'report_attachment_paths' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class)->orderBy('id');
    }

    public function recommendation(): HasOne
    {
        return $this->hasOne(Recommendation::class)->oldestOfMany();
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mission_recipient')
            ->withPivot('notified_at', 'response', 'responded_at')
            ->withTimestamps();
    }

    public function responses(): HasMany
    {
        return $this->hasMany(MissionResponse::class);
    }

    public function getTitleAttribute(): string
    {
        $first = $this->relationLoaded('recommendations')
            ? $this->recommendations->first()
            : $this->recommendations()->orderBy('id')->first();

        return $first?->recommendation_label ?? $this->reference;
    }

    public function getPeriodAttribute(): string
    {
        $start = $this->start_date?->format('d/m/Y') ?? '—';
        $end = $this->end_date?->format('d/m/Y') ?? '—';

        return "{$start} — {$end}";
    }
}
