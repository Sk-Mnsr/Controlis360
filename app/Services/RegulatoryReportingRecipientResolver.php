<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Support\Collection;

class RegulatoryReportingRecipientResolver
{
    public function resolveEntityLabel(?int $entityId): ?string
    {
        if (! $entityId) {
            return null;
        }

        return Entity::query()
            ->where('id', $entityId)
            ->where('type', 'department')
            ->value('name');
    }

    public function resolveRecipients(?int $entityId): Collection
    {
        if (! $entityId) {
            return collect();
        }

        return User::query()
            ->where('activated', true)
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('profile', 'metier')
                        ->where('metier_role', 'responsable_entite');
                })->orWhere('profile', 'conformite');
            })
            ->whereHas('entities', fn ($query) => $query->where('entities.id', $entityId))
            ->orderBy('id')
            ->get();
    }

    public function userCanReceive(User $user, ?int $entityId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $entityId) {
            return false;
        }

        return $user->belongsToEntity($entityId)
            && (
                ($user->profile === 'metier' && $user->metier_role === 'responsable_entite')
                || $user->profile === 'conformite'
            );
    }
}
