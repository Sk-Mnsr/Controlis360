<?php

namespace App\Models;

use App\Enums\UserProfile;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Maravel\Models\AuthenticatableBase;

class User extends AuthenticatableBase
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile',
        'metier_role',
        'controle_role',
        'audit_role',
        'subsidiary_id',
        'department_id',
        'job_title',
        'ad_object_id',
        'activated',
        'password_change_required',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public $appends = [
        'workspace',
        'environment_ids',
        'entity_ids',
    ];

    protected $enumCasts = [
        [
            'colum_name' => 'profile',
            'additional_column_name' => 'profile_fr',
            'choices' => [
                'super_admin' => 'Super administrateur',
                'admin' => 'Administrateur',
                'superviseur' => 'Superviseur',
                'regulateur' => 'Régulateur',
                'controle' => 'Contrôle',
                'audit' => 'Audit',
                'metier' => 'Métier',
            ],
        ],
        [
            'colum_name' => 'profile',
            'additional_column_name' => 'ability_rules',
            'choices' => [
                'super_admin' => [
                    ['subject' => ['all'], 'action' => ['manage']],
                ],
                'admin' => [
                    ['subject' => ['entity', 'user'], 'action' => ['create', 'read', 'update', 'delete']],
                ],
                'superviseur' => [
                    ['subject' => ['evaluation', 'report', 'entity'], 'action' => ['read', 'update', 'validate']],
                ],
                'regulateur' => [
                    ['subject' => ['recommendation', 'mission'], 'action' => ['read', 'validate']],
                ],
                'controle' => [
                    ['subject' => ['evaluation', 'anomaly', 'recommendation', 'mission'], 'action' => ['create', 'read', 'update', 'validate']],
                ],
                'audit' => [
                    ['subject' => ['recommendation', 'mission'], 'action' => ['create', 'read', 'update', 'validate']],
                ],
                'metier' => [
                    ['subject' => ['evaluation', 'report'], 'action' => ['read']],
                ],
            ],
        ],
        [
            'colum_name' => 'metier_role',
            'additional_column_name' => 'metier_role_fr',
            'choices' => [
                'responsable_entite' => 'Responsable entité',
                'groupe' => 'Groupe',
                'visiteur' => 'Visiteur',
                'agent' => 'Agent',
            ],
        ],
        [
            'colum_name' => 'controle_role',
            'additional_column_name' => 'controle_role_fr',
            'choices' => [
                'agent_controle_interne' => 'Agent du contrôle interne',
                'responsable_controle_permanent' => 'Responsable Contrôle permanent & risques opérationnels',
            ],
        ],
        [
            'colum_name' => 'audit_role',
            'additional_column_name' => 'audit_role_fr',
            'choices' => [
                'agent_audit' => 'Agent audit',
                'responsable_audit' => 'Responsable audit',
            ],
        ],
        [
            'colum_name' => 'activated',
            'additional_column_name' => 'activated_fr',
            'choices' => [
                1 => 'Oui',
                0 => 'Non',
            ],
        ],
        [
            'colum_name' => 'password_change_required',
            'additional_column_name' => 'password_change_required_fr',
            'choices' => [
                1 => 'Obligatoire',
                0 => 'Facultatif',
            ],
        ],
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activated' => 'boolean',
            'password_change_required' => 'boolean',
        ];
    }

    public function getWorkspaceAttribute(): string
    {
        return UserProfile::tryFrom($this->profile)?->workspace() ?? 'metier';
    }

    public function getEnvironmentIdsAttribute(): array
    {
        if ($this->relationLoaded('environments')) {
            return $this->environments->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        return $this->environments()->pluck('environments.id')->map(fn ($id) => (int) $id)->all();
    }

    public function getEntityIdsAttribute(): array
    {
        if ($this->relationLoaded('entities')) {
            return $this->entities->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        return $this->entities()->pluck('entities.id')->map(fn ($id) => (int) $id)->all();
    }

    public function getEnvironmentIdAttribute(): ?int
    {
        $ids = $this->environment_ids;

        return $ids[0] ?? null;
    }

    public function getEntityIdAttribute(): ?int
    {
        $ids = $this->entity_ids;

        return $ids[0] ?? null;
    }

    public function getEnvironmentAttribute(): ?Environment
    {
        if ($this->relationLoaded('environments')) {
            return $this->environments->first();
        }

        return $this->environments()->first();
    }

    public function getEntityAttribute(): ?Entity
    {
        if ($this->relationLoaded('entities')) {
            return $this->entities->first();
        }

        return $this->entities()->first();
    }

    public function isSuperAdmin(): bool
    {
        return $this->profile === UserProfile::SuperAdmin->value;
    }

    public function isEnvironmentAdmin(): bool
    {
        return $this->profile === UserProfile::Admin->value;
    }

    public function belongsToEnvironment(?int $environmentId): bool
    {
        if ($environmentId === null) {
            return false;
        }

        return in_array($environmentId, $this->environment_ids, true);
    }

    public function belongsToEntity(?int $entityId): bool
    {
        if ($entityId === null) {
            return false;
        }

        return in_array($entityId, $this->entity_ids, true);
    }

    public function sharesEnvironmentWith(User $other): bool
    {
        return ! empty(array_intersect($this->environment_ids, $other->environment_ids));
    }

    public function environments(): BelongsToMany
    {
        return $this->belongsToMany(Environment::class)->withTimestamps();
    }

    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
