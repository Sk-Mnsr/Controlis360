<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GouvernanceItActivity extends Model
{
    public const MODULES = [
        'centre_support',
        'systemes_reseaux',
        'base_donnees',
    ];

    public const SECTIONS = [
        'projets_en_cours' => 'Projets en cours',
        'chantiers_en_cours' => 'Chantiers en cours',
        'chantier_migration_si' => 'Chantier Système d\'Information Flexcube (SI)',
        'incidents' => 'INCIDENTS',
        'points_attention' => 'Points d\'Attention',
    ];

    protected $fillable = [
        'environment_id',
        'entity_id',
        'ensemble_id',
        'module_slug',
        'section',
        'sort_order',
        'title',
        'owner',
        'priorite',
        'statut',
        'date_livraison',
        'start_date',
        'finish_date',
        'lead_time_days',
        'impact',
        'commentaire',
        'attachment_paths',
        'workflow_status',
        'validation_status',
        'validated_by',
        'validated_at',
        'submitted_for_validation_at',
        'created_by',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'date_livraison' => 'date',
            'start_date' => 'date',
            'finish_date' => 'date',
            'sent_at' => 'datetime',
            'validated_at' => 'datetime',
            'submitted_for_validation_at' => 'datetime',
            'attachment_paths' => 'array',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function ensemble(): BelongsTo
    {
        return $this->belongsTo(GouvernanceItEnsemble::class, 'ensemble_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GouvernanceItActivityMessage::class, 'activity_id');
    }

    public static function computeLeadTimeDays(?string $startDate, ?string $finishDate): ?int
    {
        if (! $startDate || ! $finishDate) {
            return null;
        }

        try {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $finish = \Carbon\Carbon::parse($finishDate)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($finish->lt($start)) {
            return null;
        }

        return $start->diffInDays($finish);
    }
}
