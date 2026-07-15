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
        return $user->canEditMethodology()
            ? Response::allow()
            : Response::deny('Seuls le super administrateur et le responsable contrôle peuvent modifier les échelles');
    }

    public function update($user, Model $level): Response
    {
        return $user->canEditMethodology()
            ? Response::allow()
            : Response::deny('Seuls le super administrateur et le responsable contrôle peuvent modifier les échelles');
    }

    public function delete($user, Model $level): Response
    {
        return $user->canEditMethodology()
            ? Response::allow()
            : Response::deny('Seuls le super administrateur et le responsable contrôle peuvent modifier les échelles');
    }
}
