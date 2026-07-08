<?php

namespace App\Enums;

enum OperationalRiskRowStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case RevisionRequested = 'revision_requested';
    case Assigned = 'assigned';
    case EntitySubmitted = 'entity_submitted';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Submitted => 'En attente de validation',
            self::RevisionRequested => 'Modifications demandées',
            self::Assigned => 'Affecté à l\'entité',
            self::EntitySubmitted => 'Soumis par l\'entité',
            self::Completed => 'Complété',
        };
    }

    public function isVisibleToEntityResponsable(): bool
    {
        return in_array($this, [
            self::Assigned,
            self::EntitySubmitted,
            self::Completed,
        ], true);
    }
}
