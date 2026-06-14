<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MethodologyPage extends Model
{
    protected $fillable = [
        'slug',
        'layout',
        'title',
        'introduction',
        'sections',
        'conclusion',
        'body_html',
        'grid_data',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'grid_data' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
