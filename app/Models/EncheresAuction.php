<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncheresAuction extends Model
{
    protected $fillable = [
        'auction_key',
        'published',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'payload' => 'array',
        ];
    }
}
