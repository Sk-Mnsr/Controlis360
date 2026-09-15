<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenericAccount extends Model
{
    public const WORKFLOW_PENDING = 'pending_validation';

    public const WORKFLOW_VALIDATED = 'validated';

    protected $fillable = [
        'environment_id',
        'user_id',
        'user_name',
        'statut',
        'forgotten',
        'account_type',
        'purpose',
        'system_application',
        'owner',
        'usage',
        'privileges_role',
        'associated_nominative_account',
        'last_review_date',
        'action_observation',
        'existence_justification',
        'usage_mode',
        'interactive_access',
        'password_managed_by',
        'mfa',
        'logging_enabled',
        'periodic_review',
        'risk',
        'corrective_measure',
        'workflow_status',
        'created_by',
        'updated_by',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_review_date' => 'date',
            'validated_at' => 'datetime',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isPending(): bool
    {
        return $this->workflow_status === self::WORKFLOW_PENDING;
    }

    public function isValidated(): bool
    {
        return $this->workflow_status === self::WORKFLOW_VALIDATED;
    }
}
