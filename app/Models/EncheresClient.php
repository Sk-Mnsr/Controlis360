<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncheresClient extends Model
{
    protected $fillable = [
        'email',
        'name',
        'token_hash',
        'email_verified_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }
}
