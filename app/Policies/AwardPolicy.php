<?php

namespace App\Policies;

use App\Models\Award;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AwardPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::ADMIN,
            Role::ORGANIZER,
        ]);
    }

    public function update(User $user, Award $award): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            return true;
        }

        if ($award->hackathon->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Award $award): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            return true;
        }

        if ($award->hackathon->user_id === $user->id) {
            return true;
        }

        return false;
    }
}
