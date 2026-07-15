<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionResponse extends BaseModel
{
    protected $fillable = [
        'mission_id',
        'responsable_id',
        'response_type',
        'handling_mode',
        'assigned_agent_id',
        'workflow_status',
        'responsible_name',
        'action_start_date',
        'planned_end_date',
        'progress_rate',
        'comment',
        'action_plan',
        'needs_infrastructure_change',
        'investment_amount',
        'go_no_go',
        'attachment_paths',
        'passivity_comment',
        'passivity_attachment_paths',
        'validated_by',
        'validated_at',
        'forwarded_at',
    ];

    protected $enumCasts = [
        [
            'colum_name' => 'response_type',
            'additional_column_name' => 'response_type_fr',
            'choices' => [
                'action' => 'Action',
                'passivite' => 'Passivité',
            ],
        ],
        [
            'colum_name' => 'workflow_status',
            'additional_column_name' => 'workflow_status_fr',
            'choices' => [
                'en_saisie' => 'En saisie',
                'a_valider' => 'À valider',
                'transmis' => 'Transmis',
            ],
        ],
        [
            'colum_name' => 'go_no_go',
            'additional_column_name' => 'go_no_go_fr',
            'choices' => [
                'go' => 'Go',
                'no_go' => 'No Go',
            ],
        ],
    ];

    protected function casts(): array
    {
        return [
            'action_start_date' => 'date',
            'planned_end_date' => 'date',
            'needs_infrastructure_change' => 'boolean',
            'investment_amount' => 'decimal:2',
            'attachment_paths' => 'array',
            'passivity_attachment_paths' => 'array',
            'validated_at' => 'datetime',
            'forwarded_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
