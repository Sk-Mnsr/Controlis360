<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maravel\Models\ModelBase;

class ItContract extends ModelBase
{
    protected $fillable = [
        'sort_order',
        'file_name',
        'file_link',
        'provider',
        'beneficiary',
        'scope',
        'contract_type',
        'volume',
        'cost',
        'cost_mechanism',
        'importance',
        'start_date',
        'duration',
        'sla',
        'termination_notice',
        'signed_both',
        'discount',
        'purchase_owner',
        'comments',
        'environment_id',
        'created_by_id',
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
