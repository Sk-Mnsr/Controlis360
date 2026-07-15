<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class MissionRecipientResolver
{
    public function resolveRecipients(array $entityIds): Collection
    {
        $recipients = collect();

        foreach ($entityIds as $entityId) {
            $responsables = User::query()
                ->where('profile', 'metier')
                ->where('metier_role', 'responsable_entite')
                ->where('activated', true)
                ->whereHas('entities', fn ($query) => $query->where('entities.id', $entityId))
                ->orderBy('id')
                ->get();

            $recipient = $this->pickPreferredResponsable($responsables);

            if ($recipient) {
                $recipients->push($recipient);
            }
        }

        return $recipients->unique('id')->values();
    }

    public function resolveResponsibleNames(array $entityIds): string
    {
        return $this->resolveRecipients($entityIds)
            ->pluck('name')
            ->filter()
            ->unique()
            ->implode(', ');
    }

    private function pickPreferredResponsable(Collection $responsables): ?User
    {
        if ($responsables->isEmpty()) {
            return null;
        }

        $production = $responsables->first(fn (User $user) => $this->isProductionAccount($user));

        return $production ?? $responsables->first();
    }

    private function isProductionAccount(User $user): bool
    {
        if (str_contains($user->name, '[TEST]')) {
            return false;
        }

        if (str_contains(strtolower($user->email), 'test.')) {
            return false;
        }

        if (str_starts_with(strtolower($user->email), 'test.')) {
            return false;
        }

        return true;
    }
}
