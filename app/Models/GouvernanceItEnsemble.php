<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GouvernanceItEnsemble extends Model
{
    protected $fillable = [
        'environment_id',
        'entity_id',
        'module_slug',
        'label',
        'created_by',
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(GouvernanceItActivity::class, 'ensemble_id');
    }

    public static function makeAutoLabel(): string
    {
        return 'Ensemble du '.now()->format('d/m/Y H:i');
    }
}
