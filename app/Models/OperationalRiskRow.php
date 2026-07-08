<?php

namespace App\Models;

use App\Enums\OperationalRiskRowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maravel\Models\ModelBase;

class OperationalRiskRow extends ModelBase
{
    protected $fillable = [
        'entity_id',
        'status',
        'revision_comment',
        'assigned_entity_id',
        'deadline',
        'created_by_id',
        'validated_by_id',
        'submitted_at',
        'validated_at',
        'process_number',
        'process_name',
        'ratio',
        'sub_process_name',
        'line_date',
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
            'line_date' => 'date',
            'deadline' => 'date',
            'residual_probability' => 'decimal:1',
            'submitted_at' => 'datetime',
            'validated_at' => 'datetime',
            'status' => OperationalRiskRowStatus::class,
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function assignedEntity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'assigned_entity_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_id');
    }

    public static function computeResidualProbability(?int $probability, ?int $effectiveness): ?float
    {
        if ($probability === null || $effectiveness === null || $effectiveness < 1) {
            return null;
        }

        $value = round($probability / $effectiveness, 1);

        return max(1.0, min(6.0, $value));
    }

    public function resolvedResidualGravity(): ?int
    {
        return $this->residual_gravity ?? $this->gravity;
    }

    public function resolvedResidualProbability(): ?float
    {
        if ($this->residual_probability !== null) {
            return (float) $this->residual_probability;
        }

        return self::computeResidualProbability($this->probability, $this->control_effectiveness);
    }

    public function getGrossRiskAttribute(): ?int
    {
        if ($this->gravity === null || $this->probability === null) {
            return null;
        }

        return $this->gravity * $this->probability;
    }

    public function getResidualRiskAttribute(): ?float
    {
        $gravity = $this->resolvedResidualGravity();
        $probability = $this->resolvedResidualProbability();

        if ($gravity === null || $probability === null) {
            return null;
        }

        return round($gravity * $probability, 1);
    }

    public function canEditPhase1By(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->isSuperAdmin() && ! $user->canCreateOperationalRiskRow()) {
            return false;
        }

        return in_array($this->status, [
            OperationalRiskRowStatus::Draft,
            OperationalRiskRowStatus::RevisionRequested,
        ], true);
    }

    public function canEditPhase2By(User $user): bool
    {
        if ($this->status !== OperationalRiskRowStatus::Assigned) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isEntityResponsable()
            && (int) $user->entity_id === (int) $this->assigned_entity_id;
    }

    public function scopeVisibleToEntityResponsable(Builder $query, User $user): Builder
    {
        if (! $user->isEntityResponsable() || ! $user->entity_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('assigned_entity_id', $user->entity_id)
            ->whereIn('status', [
                OperationalRiskRowStatus::Assigned,
                OperationalRiskRowStatus::EntitySubmitted,
                OperationalRiskRowStatus::Completed,
            ]);
    }

    public function scopeVisibleInAnalyse(Builder $query): Builder
    {
        return $query->where('status', '!=', OperationalRiskRowStatus::Draft);
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->isControleAgent() || $user->isControleResponsable()) {
            return true;
        }

        if (! $user->isEntityResponsable() || ! $user->entity_id) {
            return false;
        }

        $status = $this->status instanceof OperationalRiskRowStatus
            ? $this->status
            : OperationalRiskRowStatus::tryFrom((string) $this->status);

        return (int) $this->assigned_entity_id === (int) $user->entity_id
            && $status?->isVisibleToEntityResponsable();
    }
}
