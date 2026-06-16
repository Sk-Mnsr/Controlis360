<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class ScaleLevelPolicy
{
    public function viewAny($user): Response
    {
        return Response::allow();
    }

    public function view($user, Model $level): Response
    {
        return Response::allow();
    }

    public function create($user): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('Seul le super administrateur peut modifier les échelles');
    }

    public function update($user, Model $level): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('Seul le super administrateur peut modifier les échelles');
    }

    public function delete($user, Model $level): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('Seul le super administrateur peut modifier les échelles');
    }
}
