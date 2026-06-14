<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Maravel\Policies\BasePolicy;

class UserPolicy extends BasePolicy
{
    protected $modelName = 'user';

    public function before($connectedUser, string $ability)
    {
        if ($connectedUser->isSuperAdmin()) {
            return Response::allow();
        }

        return null;
    }

    private function adminManagesUser($admin, Model $user): bool
    {
        return $admin->isEnvironmentAdmin()
            && $admin->belongsToEnvironment($user->environment_id)
            && $user->profile !== 'super_admin';
    }

    public function viewAny($connectedUser)
    {
        if ($connectedUser->isEnvironmentAdmin() && $connectedUser->environment_id) {
            return Response::allow();
        }

        return parent::viewAny($connectedUser);
    }

    public function view($connectedUser, Model $user)
    {
        if ($connectedUser->id === $user->id) {
            return Response::allow();
        }

        if ($this->adminManagesUser($connectedUser, $user)) {
            return Response::allow();
        }

        return parent::view($connectedUser, $user);
    }

    public function create($connectedUser)
    {
        if ($connectedUser->isEnvironmentAdmin() && $connectedUser->environment_id) {
            return Response::allow();
        }

        return parent::create($connectedUser);
    }

    public function update($connectedUser, Model $user)
    {
        if ($connectedUser->id === $user->id) {
            return Response::allow();
        }

        if ($this->adminManagesUser($connectedUser, $user)) {
            return Response::allow();
        }

        return parent::update($connectedUser, $user);
    }

    public function delete($connectedUser, Model $user)
    {
        if ($connectedUser->id === $user->id) {
            return Response::deny('Vous ne pouvez pas supprimer votre propre compte');
        }

        if ($this->adminManagesUser($connectedUser, $user)) {
            return Response::allow();
        }

        return parent::delete($connectedUser, $user);
    }

    public function updatePassword($connectedUser)
    {
        return Response::allow();
    }
}
