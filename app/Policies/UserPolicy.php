<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function blockUser(User $user): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        return $user->isAdmin();
    }

    public function changeRoles(User $user): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        return $user->isTopAdmin();
    }
}
