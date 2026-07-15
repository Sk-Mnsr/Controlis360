<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Maravel\Policies\BasePolicy;

class EntityPolicy extends BasePolicy
{
    protected $modelName = 'entity';

    public function before($connectedUser, string $ability)
    {
        if ($connectedUser->isSuperAdmin()) {
            return Response::allow();
        }

        return null;
    }

    private function adminOwnsEntity($user, Model $model): bool
    {
        return $user->isEnvironmentAdmin()
            && $user->belongsToEnvironment($model->environment_id);
    }

    public function viewAny($connectedUser)
    {
        if ($connectedUser->isEnvironmentAdmin() && ! empty($connectedUser->environment_ids)) {
            return Response::allow();
        }

        return parent::viewAny($connectedUser);
    }

    public function view($connectedUser, Model $model)
    {
        if ($this->adminOwnsEntity($connectedUser, $model)) {
            return Response::allow();
        }

        return parent::view($connectedUser, $model);
    }

    public function create($connectedUser)
    {
        if ($connectedUser->isEnvironmentAdmin() && ! empty($connectedUser->environment_ids)) {
            return Response::allow();
        }

        return parent::create($connectedUser);
    }

    public function update($connectedUser, Model $model)
    {
        if ($this->adminOwnsEntity($connectedUser, $model)) {
            return Response::allow();
        }

        return parent::update($connectedUser, $model);
    }

    public function delete($connectedUser, Model $model)
    {
        if ($this->adminOwnsEntity($connectedUser, $model)) {
            return Response::allow();
        }

        return parent::delete($connectedUser, $model);
    }
}
