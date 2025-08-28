<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function blockUser(User $user): bool
    {
        return $user->isAdmin();
    }

    public function changeRoles(User $user): bool
    {
        return $user->isAdmin();
    }
}
