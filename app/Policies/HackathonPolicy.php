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
            return $hackathon->status !== Hackathon::STATUS_DRAFT;
        }

        return $hackathon->status === Hackathon::STATUS_PUBLISHED || $user->isHackathonStaff($hackathon);
    }

    public function viewTask(?User $user, Hackathon $hackathon): bool
    {
        if (!$user) {
            return false;
        }

        $isHackathonPublish = $hackathon->status === Hackathon::STATUS_PUBLISHED && $hackathon->event_start->lessThan(now());

        return $isHackathonPublish || $user->isHackathonStaff($hackathon);
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
        if ($hackathon->status === Hackathon::STATUS_PUBLISHED) {
            return false;
        }

        return $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
    }

    public function join(?User $user, Hackathon $hackathon): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isHackathonStaff($hackathon)) {
            return false;
        }

        if ($user->hackathons()->where('hackathon_id', $hackathon->id)->exists()) {
            return false;
        }

        if ($user->hasRole(Role::MEMBER)) {
            return $hackathon->status === Hackathon::STATUS_PUBLISHED;
        }

        return false;
    }

    public function acceptInvite(?User $user, Hackathon $hackathon): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isHackathonStaff($hackathon)) {
            return false;
        }

        if ($user->hasRole(Role::MEMBER)) {
            return $hackathon->status === Hackathon::STATUS_PUBLISHED;
        }

        return false;
    }

    public function leave(User $user, Hackathon $hackathon): bool
    {
        if ($user->isHackathonStaff($hackathon)) {
            return false;
        }

        if (!$user->hackathons()->where('hackathon_id', $hackathon->id)->exists()) {
            return false;
        }

        $team = $user->teams()
            ->where('hackathon_id', $hackathon->id)
            ->first();

        if ($team && $team->users()->count() > 1) {
            return false;
        }

        if ($user->hasRole(Role::MEMBER)) {
            return $hackathon->status === Hackathon::STATUS_PUBLISHED;
        }

        return false;
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
