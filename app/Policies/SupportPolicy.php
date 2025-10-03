<?php

namespace App\Policies;

use App\Models\Hackathon;
use App\Models\Support;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Hackathon $hackathon): bool
    {
        $isMember = $user->hackathons()->where('hackathons.id', $hackathon->id)->exists();
        $isOrganizer = $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();

        return $isMember || $isOrganizer;
    }

    public function createSupport(User $user, Hackathon $hackathon): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        $isMember = $user->hackathons()->where('hackathons.id', $hackathon->id)->exists();
        $isOrganizer = $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();

        return $isMember || $isOrganizer;
    }

    public function answer(User $user, Support $support): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($support->is_completed) {
            return false;
        }

        $hackathon = $support?->hackathon;
        return match ($support->type) {
            Support::BUG => $user->isAdmin(),
            Support::SUGGESTION, Support::QUESTION => $hackathon && $user->isHackathonStaff($hackathon),
            default => false,
        };
    }

    public function read(User $user, Support $support): bool
    {
        $hackathon = $support?->hackathon;
        return match ($support->type) {
            Support::BUG => $user->isAdmin(),
            Support::SUGGESTION, Support::QUESTION => $hackathon && $user->isHackathonStaff($hackathon),
            default => false,
        };
    }
}
