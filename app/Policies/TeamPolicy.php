<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Team $team): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        $hackathonEndDate = $team->hackathon->event_end;
        if ($hackathonEndDate->lessThan(now())) {
            return false;
        }

        return $user->teams()->wherePivot('position_id', Position::CAPITAN_POSITION)->where('id', $team->id)->exists();
    }

    public function kick(User $user, Team $team): bool
    {
        $hackathonStartDate = $team->hackathon->event_start;
        if ($hackathonStartDate->lessThan(now())) {
            return false;
        }

        return $user->teams()->wherePivot('position_id', Position::CAPITAN_POSITION)->where('id', $team->id)->exists();
    }

    public function joinTeam(User $user, Team $team): bool
    {
        $hackathon = $team->hackathon;
        if ($hackathon->event_start->lessThan(now())) {
            return false;
        }

        $teamCount = $team->users->count();
        if ($teamCount < $hackathon->max_team_size) {
            return true;
        }

        return false;
    }
}
