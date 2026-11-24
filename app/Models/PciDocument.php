<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PciDocument extends Model
{
    public const WORKFLOW_PENDING = 'pending_validation';

    public const WORKFLOW_VALIDATED = 'validated';

    protected $fillable = [
        'environment_id',
        'reference',
        'document_name',
        'category',
        'direction',
        'gouvernance_owner',
        'controle_interne',
        'responsible',
        'created_on',
        'last_review_date',
        'periodicity_months',
        'validated_on',
        'validated_by',
        'version',
        'observations',
        'attachment_paths',
        'workflow_status',
        'workflow_validated_by',
        'workflow_validated_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'created_on' => 'date',
            'last_review_date' => 'date',
            'validated_on' => 'date',
            'periodicity_months' => 'integer',
            'attachment_paths' => 'array',
            'workflow_validated_at' => 'datetime',
        ];
    }

    public function workflowValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'workflow_validated_by');
    }

    public function isPending(): bool
    {
        return $this->workflow_status === self::WORKFLOW_PENDING;
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

    /**
     * Prochaine revue, jours restants, statut et niveau de rappel.
     *
     * @return array{
     *     status: string,
     *     status_label: string,
     *     next_review_date: ?string,
     *     days_remaining: ?int,
     *     reminder: ?string,
     *     reminder_label: ?string
     * }
     */
    public function reviewState(): array
    {
        $name = trim((string) $this->document_name);

        if ($name === '' || ! $this->last_review_date) {
            return [
                'status' => 'a_renseigner',
                'status_label' => 'À renseigner',
                'next_review_date' => null,
                'days_remaining' => null,
                'reminder' => null,
                'reminder_label' => null,
            ];
        }

        $months = max(1, (int) ($this->periodicity_months ?: 12));
        $next = $this->last_review_date->copy()->startOfDay()->addMonths($months);
        $today = now()->startOfDay();
        $days = (int) round(($next->timestamp - $today->timestamp) / 86400);

        if ($days < 0) {
            $status = 'en_retard';
            $label = 'En retard';
            $reminder = 'apres';
            $reminderLabel = 'Après échéance';
        } elseif ($days <= 7) {
            $status = 'echeance_7';
            $label = 'Échéance ≤ 7 jours';
            $reminder = 'j7';
            $reminderLabel = 'J-7';
        } elseif ($days <= 90) {
            $status = 'echeance_90';
            $label = 'Échéance ≤ 90 jours';
            $reminder = $days <= 30 ? 'j30' : 'j90';
            $reminderLabel = $days <= 30 ? 'J-30' : 'J-90';
        } else {
            $status = 'a_jour';
            $label = 'À jour';
            $reminder = null;
            $reminderLabel = null;
        }

        return [
            'status' => $status,
            'status_label' => $label,
            'next_review_date' => $next->toDateString(),
            'days_remaining' => $days,
            'reminder' => $reminder,
            'reminder_label' => $reminderLabel,
        ];
    }
}
