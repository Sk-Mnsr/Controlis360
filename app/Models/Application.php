<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maravel\Models\ModelBase;

class Application extends ModelBase
{
    protected $fillable = [
        'code',
        'name',
        'business_domain',
        'main_function',
        'users',
        'technology',
        'type',
        'criticality',
        'status',
        'owner',
        'go_live_date',
        'license_version',
        'license_expiry_date',
        'license_update',
        'license_last_renewal_date',
        'license_next_renewal_date',
        'infrastructure',
        'hosting_type',
        'backup',
        'sla',
        'comment',
        'cost',
        'impact',
        'risk',
        'last_version',
        'environment_id',
        'entity_id',
        'created_by_id',
    ];

    protected $enumCasts = [
        [
            'colum_name' => 'status',
            'additional_column_name' => 'status_fr',
            'choices' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'planned' => 'Planifiée',
                'retired' => 'Retirée',
            ],
        ],
        [
            'colum_name' => 'criticality',
            'additional_column_name' => 'criticality_fr',
            'choices' => [
                'faible' => 'Faible',
                'moyenne' => 'Moyenne',
                'haute' => 'Haute',
                'critique' => 'Critique',
            ],
        ],
    ];

    protected function casts(): array
    {
        return [
            'go_live_date' => 'date',
            'license_expiry_date' => 'date',
            'license_last_renewal_date' => 'date',
            'license_next_renewal_date' => 'date',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
