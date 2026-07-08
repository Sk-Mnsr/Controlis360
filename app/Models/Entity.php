<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maravel\Models\ModelBase;

class Entity extends ModelBase
{
    protected $fillable = [
        'environment_id',
        'type',
        'name',
        'code',
        'is_active',
        'sort_order',
    ];

    protected $enumCasts = [
        [
            'colum_name' => 'type',
            'additional_column_name' => 'type_fr',
            'choices' => [
                'department' => 'Département',
                'agency' => 'Agence',
            ],
        ],
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function responsables(): BelongsToMany
    {
        return $this->users()
            ->where('profile', 'metier')
            ->where('metier_role', 'responsable_entite')
            ->where('activated', true);
    }

    public function operationalRiskRows(): HasMany
    {
        return $this->hasMany(OperationalRiskRow::class);
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->environment_id) {
            $query->where('environment_id', $user->environment_id);
        }

        if ($user->isEntityResponsable() && $user->entity_id) {
            $query->where('id', $user->entity_id);
        } elseif ($user->isEntityResponsable()) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function resolveDepartmentForUser(
        User $user,
        string $code,
        ?string $environmentCode = null,
        ?int $entityId = null
    ): ?self {
        $query = static::query()
            ->with('environment')
            ->whereIn('type', ['department', 'agency'])
            ->where('code', $code)
            ->where('is_active', true)
            ->visibleToUser($user);

        if ($entityId) {
            return $query->where('id', $entityId)->first();
        }

        if ($environmentCode) {
            return $query
                ->whereHas('environment', fn (Builder $environmentQuery) => $environmentQuery->where('code', $environmentCode))
                ->first();
        }

        $matches = $query->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
