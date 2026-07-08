<?php

namespace App\Models;

use App\Enums\UserProfile;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'environment_id',
        'entity_id',
        'metier_role',
        'controle_role',
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
    ];

    protected $enumCasts = [
        [
            'colum_name' => 'profile',
            'additional_column_name' => 'profile_fr',
            'choices' => [
                'super_admin' => 'Super administrateur',
                'admin' => 'Administrateur',
                'controle' => 'Contrôle',
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
                'controle' => [
                    ['subject' => ['evaluation', 'anomaly'], 'action' => ['create', 'read', 'update', 'validate']],
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

    public function isSuperAdmin(): bool
    {
        return $this->profile === UserProfile::SuperAdmin->value;
    }

    public function isEnvironmentAdmin(): bool
    {
        return $this->profile === UserProfile::Admin->value;
    }

    public function isControleAgent(): bool
    {
        return $this->profile === UserProfile::Controle->value
            && $this->controle_role === 'agent_controle_interne';
    }

    public function isControleResponsable(): bool
    {
        return $this->profile === UserProfile::Controle->value
            && $this->controle_role === 'responsable_controle_permanent';
    }

    public function canEditMethodology(): bool
    {
        return $this->isSuperAdmin() || $this->isControleResponsable();
    }

    public function canCreateOperationalRiskRow(): bool
    {
        return $this->isSuperAdmin() || $this->isControleAgent() || $this->isControleResponsable();
    }

    public function isEntityResponsable(): bool
    {
        return $this->profile === UserProfile::Metier->value
            && $this->metier_role === 'responsable_entite';
    }

    public function belongsToEnvironment(?int $environmentId): bool
    {
        return $this->environment_id !== null && $this->environment_id === $environmentId;
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
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
