<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class ItContractPolicy
{
    public function before(User $user, string $ability): ?Response
    {
        if ($user->isPlatformAdministrator() || $user->isSuperAdmin()) {
            return Response::allow();
        }

        return null;
    }

    public function viewAny(User $user): Response
    {
        return $this->canAccess($user)
            ? Response::allow()
            : Response::deny('Accès non autorisé aux contrats IT');
    }

    public function view(User $user, Model $model): Response
    {
        return $this->viewAny($user);
    }

    public function create(User $user): Response
    {
        return $this->canWrite($user)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à créer un contrat IT');
    }

    public function update(User $user, Model $model): Response
    {
        return $this->canWrite($user)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à modifier un contrat IT');
    }

    public function delete(User $user, Model $model): Response
    {
        return $this->canWrite($user)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à supprimer un contrat IT');
    }

    private function canAccess(User $user): bool
    {
        return $user->hasModuleAccess('gouvernance-it')
            || in_array($user->moduleProfile('gouvernance-it') ?? $user->profile, [
                'super_admin', 'admin', 'agent_it', 'responsable_it', 'responsable_regional',
            ], true);
    }

    private function canWrite(User $user): bool
    {
        return in_array($user->moduleProfile('gouvernance-it') ?? $user->profile, [
            'super_admin', 'admin', 'agent_it', 'responsable_it',
        ], true);
    }
}
