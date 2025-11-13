<?php

namespace App\Policies;

use App\Models\Hackathon;
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

    public function viewAll(User $user, Hackathon $hackathon): bool
    {
        return $user->isHackathonStaff($hackathon);
    }

    public function view(?User $user, Team $team): bool
    {
        return $user?->teams()->where('teams.id', $team->id)->exists() ?? false;
    }

    public function update(User $user, Team $team): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        $hackathonEndDate = $team->hackathon->event_end;
        if ($hackathonEndDate->lessThan(now())) {
            return false;
        }

        if ($team->hackathon->type === "individual") {
            return false;
        }

        if ($team->hackathon->type === "team" && $team->hackathon->max_team_size === 1) {
            return false;
        }

        return $user->teams()
            ->wherePivot('position_id', Position::CAPITAN_POSITION)
            ->where('teams.id', $team->id)
            ->exists();
    }

    public function kick(User $user, Team $team): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        $hackathonStartDate = $team->hackathon->event_start;
        if ($hackathonStartDate->lessThan(now())) {
            return false;
        }

        return $user->teams()
            ->wherePivot('position_id', Position::CAPITAN_POSITION)
            ->where('teams.id', $team->id)
            ->exists();
    }

    public function invite(User $user, Team $team): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        $hackathon = $team->hackathon;
        if ($hackathon->event_start->lessThan(now())) {
            return false;
        }

        $teamCount = $team->users->count();
        return $teamCount < $hackathon->max_team_size;
    }

    public function joinTeam(User $user, Team $team): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        $hackathon = $team->hackathon;
        if ($hackathon->event_start->lessThan(now())) {
            return false;
        }

        $teamCount = $team->users->count();
        return $teamCount < $hackathon->max_team_size;
    }
}
