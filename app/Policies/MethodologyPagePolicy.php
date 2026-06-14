<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class MethodologyPagePolicy
{
    public function viewAny($user): Response
    {
        return Response::allow();
    }

    public function view($user, Model $page): Response
    {
        return $page->is_active ? Response::allow() : Response::deny('Page inactive');
    }

    public function update($user, Model $page): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('Seul le super administrateur peut modifier ce contenu');
    }
}
