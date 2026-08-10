<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItService extends BaseModel
{
    protected $fillable = [
        'application_type_id',
        'exists_flag',
        'solution_name',
        'editor',
        'importance',
        'version',
        'last_version',
        'sla_exists',
        'hosting_mode',
        'users_count',
        'licenses_count',
        'license_type',
        'customization_level',
        'backups',
        'etp_support',
        'archi_ho',
        'environment_id',
        'updated_by_id',
    ];

    public function applicationType(): BelongsTo
    {
        return $this->belongsTo(ApplicationType::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
