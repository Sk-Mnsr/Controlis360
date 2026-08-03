<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GouvernanceItActivityMessage extends Model
{
    protected $fillable = [
        'activity_id',
        'user_id',
        'body',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(GouvernanceItActivity::class, 'activity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
