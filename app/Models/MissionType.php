<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class MissionType extends BaseModel
{
    protected $fillable = [
        'code',
        'name',
        'profiles',
        'description',
        'accent_color',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'profiles' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'mission_type', 'code');
    }
}
