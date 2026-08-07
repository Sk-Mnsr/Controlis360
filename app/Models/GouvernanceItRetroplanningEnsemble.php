<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GouvernanceItRetroplanningEnsemble extends Model
{
    protected $fillable = [
        'activity_id',
        'label',
        'sort_order',
    ];

    public function parentActivity(): BelongsTo
    {
        return $this->belongsTo(GouvernanceItActivity::class, 'activity_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GouvernanceItRetroplanningItem::class, 'ensemble_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
