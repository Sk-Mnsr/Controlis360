<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maravel\Models\ModelBase;

class OperationalRiskLog extends ModelBase
{
    public $timestamps = false;

    protected $fillable = [
        'operational_risk_row_id',
        'entity_id',
        'user_id',
        'action',
        'message',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(OperationalRiskRow::class, 'operational_risk_row_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'created' => 'Création',
            'updated' => 'Modification',
            'submitted' => 'Soumission',
            'revision_requested' => 'Demande de modifications',
            'validated' => 'Validation et affectation',
            'entity_submitted' => 'Soumission par l\'entité',
            'entity_revision_requested' => 'Modifications demandées à l\'entité',
            'completed' => 'Complétion',
            'deleted' => 'Suppression',
            default => $this->action,
        };
    }
}
