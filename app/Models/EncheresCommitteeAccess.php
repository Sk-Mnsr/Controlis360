<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncheresCommitteeAccess extends Model
{
    protected $fillable = [
        'auction_key',
        'lot',
        'title',
        'code_hash',
        'committee',
        'bids',
        'attempts',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'committee' => 'array',
            'bids' => 'array',
            'attempts' => 'integer',
        ];
    }
};
