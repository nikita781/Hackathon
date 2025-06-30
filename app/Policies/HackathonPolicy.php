<?php

namespace App\Policies;

use App\Models\Hackathon;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HackathonPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Hackathon $hackathon): bool
    {
        if (!$user) {
            return $hackathon->is_published;
        }

        return $hackathon->is_published || $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::ORGANIZER);
    }

    public function update(User $user, Hackathon $hackathon): bool
    {
        return $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
    }

    public function delete(User $user, Hackathon $hackathon): bool
    {
        if ($hackathon->is_published) {
            return false;
        }

        return $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
    }

    public function join(?User $user, Hackathon $hackathon): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists()) {
            return false;
        }

        if ($user->hackathons()->where('hackathon_id', $hackathon->id)->exists()) {
            return false;
        }

        return $hackathon->is_published;
    }

    public function evaluation(User $user, Hackathon $hackathon): bool
    {
        if ($user->hasRole(Role::ADMIN)) {
            return true;
        }

        if ($user->hasRole(Role::ORGANIZER)) {
            return $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
        }

        return $user->hackathons()->where('id', $hackathon->id)->where('role_id', Role::JUDGE)->exists();
    }
}
