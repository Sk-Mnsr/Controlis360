<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApplicationType extends BaseModel
{
    protected $fillable = [
        'code',
        'name',
        'accent_color',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function itService(): HasOne
    {
        return $this->hasOne(ItService::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ApplicationQuestion::class)->where('scope', 'type');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ApplicationAnswer::class);
    }
}
