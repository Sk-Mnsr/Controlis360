<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class ApplicationPolicy
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
        return $this->canAccessInventory($user)
            ? Response::allow()
            : Response::deny('Accès non autorisé à l’inventaire des applications');
    }

    public function view(User $user, Model $application): Response
    {
        return $this->viewAny($user);
    }

    public function create(User $user): Response
    {
        return $this->canWriteInventory($user)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à créer une application');
    }

    public function update(User $user, Model $application): Response
    {
        return $this->canWriteInventory($user)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à modifier une application');
    }

    public function delete(User $user, Model $application): Response
    {
        return $this->canWriteInventory($user)
            ? Response::allow()
            : Response::deny('Vous n’êtes pas autorisé à supprimer une application');
    }

    private function gouvernanceProfile(User $user): ?string
    {
        return $user->moduleProfile('gouvernance-it') ?? $user->profile;
    }

    private function canAccessInventory(User $user): bool
    {
        if ($user->hasModuleAccess('gouvernance-it') || $user->hasModuleAccess('cartographie-applications')) {
            return true;
        }

        return in_array($this->gouvernanceProfile($user), [
            'super_admin',
            'admin',
            'agent_it',
            'responsable_it',
            'responsable_regional',
        ], true);
    }

    private function canWriteInventory(User $user): bool
    {
        $profile = $this->gouvernanceProfile($user);

        return in_array($profile, [
            'super_admin',
            'admin',
            'agent_it',
            'responsable_it',
        ], true);
    }
}
