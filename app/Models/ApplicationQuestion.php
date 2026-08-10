<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationQuestion extends BaseModel
{
    protected $fillable = [
        'scope',
        'application_type_id',
        'label',
        'help',
        'input_type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function applicationType(): BelongsTo
    {
        return $this->belongsTo(ApplicationType::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ApplicationAnswer::class, 'question_id');
    }
}
