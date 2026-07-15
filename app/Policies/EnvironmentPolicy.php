<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Maravel\Policies\BasePolicy;

class EnvironmentPolicy extends BasePolicy
{
    protected $modelName = 'environment';

    public function before($connectedUser, string $ability)
    {
        if ($connectedUser->isSuperAdmin()) {
            return Response::allow();
        }

        return null;
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
        if ($connectedUser->isEnvironmentAdmin() && $connectedUser->belongsToEnvironment($model->id)) {
            return Response::allow();
        }

        return parent::view($connectedUser, $model);
    }

    public function create($connectedUser)
    {
        return Response::deny('Seul le super administrateur peut créer des environnements');
    }

    public function update($connectedUser, Model $model)
    {
        if ($connectedUser->isEnvironmentAdmin() && $connectedUser->belongsToEnvironment($model->id)) {
            return Response::allow();
        }

        return parent::update($connectedUser, $model);
    }

    public function delete($connectedUser, Model $model)
    {
        return Response::deny('Seul le super administrateur peut supprimer des environnements');
    }
}
