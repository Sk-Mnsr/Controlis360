<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maravel\Models\ModelBase;

class SubProcess extends ModelBase
{
    protected $fillable = [
        'process_id',
        'name',
        'sort_order',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }
}
