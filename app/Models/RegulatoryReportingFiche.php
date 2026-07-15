<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegulatoryReportingFiche extends BaseModel
{
    protected $fillable = [
        'fiche_number',
        'type_reporting',
        'destinataires',
        'reference',
        'pj_required',
        'attachment_paths',
        'elements',
        'canals',
        'periodicites',
        'deposant',
        'deposant_entity_id',
        'etabli_par',
        'etabli_par_entity_id',
        'date_validation',
        'delai_transmission',
        'environment_id',
        'created_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'destinataires' => 'array',
            'elements' => 'array',
            'canals' => 'array',
            'periodicites' => 'array',
            'attachment_paths' => 'array',
            'pj_required' => 'boolean',
            'date_validation' => 'date',
            'delai_transmission' => 'date',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deposantEntity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'deposant_entity_id');
    }

    public function etabliParEntity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'etabli_par_entity_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(RegulatoryReportingContribution::class, 'fiche_id')->orderBy('id');
    }
}
