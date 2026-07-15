<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maravel\Models\ModelBase;

class RiskFamily extends ModelBase
{
    protected $fillable = [
        'risk_category_id',
        'name',
        'definition',
        'sort_order',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(RiskCategory::class, 'risk_category_id');
    }
}
