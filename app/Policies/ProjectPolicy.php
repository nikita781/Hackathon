<?php

namespace App\Policies;

use App\Models\Hackathon;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function viewAll(User $user, Hackathon $hackathon): bool
    {
        if ($hackathon->event_start < now()) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isHackathonStaff($hackathon);
    }

    public function view(?User $user, Project $project): bool
    {
        if ($project->status === Project::PUBLISHED) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isHackathonStaff(Hackathon::find($project->hackathon_id))) {
            return true;
        }

        return $user->isMemberOfProject($project);
    }

    public function createProject(User $user, Hackathon $hackathon): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($hackathon->work_time_start > now() || $hackathon->work_time_end < now()) {
            return false;
        }

        $team = $user->teams()->where('hackathon_id', $hackathon->id)->first();

        if (!$team) {
            return false;
        }

        if ($team?->projects?->count() >= 4) {
            return false;
        }

        $publishedProjects = Project::query()
            ->where('team_id', $team?->id)
            ->whereIn('status', [Project::PUBLISHED, Project::MODERATION])
            ->exists();

        return $user->isCapitanOfHackathon($hackathon) && !$publishedProjects;
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($project->status === Project::PUBLISHED || $project->status === Project::MODERATION) {
            return false;
        }

        $hackathon = Hackathon::findOrFail($project->hackathon_id);
        if ($hackathon->work_time_start > now() || now() > $hackathon->work_time_end) {
            return false;
        }

        return $user->isCapitan($project);
    }

    public function delete(User $user, Project $project): bool
    {
        if ($project->status === Project::PUBLISHED || $project->status === Project::MODERATION) {
            return false;
        }

        return $user->isCapitan($project);
    }

    public function publish(User $user, Project $project): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        $hackathon = Hackathon::findOrFail($project->hackathon_id);
        if ($hackathon->work_time_start > now() || $hackathon->work_time_end < now()) {
            return false;
        }

        $publishedProjects = Project::query()
            ->where('team_id', $project->team_id)
            ->whereIn('status', [Project::PUBLISHED, Project::MODERATION])
            ->exists();
        return $user->isCapitan($project) && !$publishedProjects;
    }

    public function moderate(User $user): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        return $user->isAdmin();
    }

    public function viewSourceCode(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isMemberOfProject($project) || $user->isHackathonStaff($project->hackathon()->first())) {
            return true;
        }

        return false;
    }
}
