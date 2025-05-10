<?php

namespace App\Policies;

use App\Models\Hackathon;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HackathonPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Hackathon $hackathon): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::ORGANIZER);
    }

    public function update(User $user, Hackathon $hackathon): bool
    {
        return $user->hackathons()->where('hackathon_id', $hackathon->id)->where('role_id', Role::ORGANIZER)->exists();
    }

    public function delete(User $user, Hackathon $hackathon): bool
    {
        return $user->hackathons()->where('hackathon_id', $hackathon->id)->where('role_id', Role::ORGANIZER)->exists();
    }
}
