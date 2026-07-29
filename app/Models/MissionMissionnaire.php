<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionMissionnaire extends BaseModel
{
    protected $table = 'mission_missionnaires';

    protected $fillable = [
        'mission_id',
        'nom',
        'email',
        'telephone',
        'poste',
        'entite_type',
        'responsable_equipe',
        'ordre',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
