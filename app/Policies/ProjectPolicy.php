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
        if ($hackathon->work_time_start > now() && $hackathon->work_time_end < now()) {
            return false;
        }

        return $user->isCapitanOfHackathon($hackathon);
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($project->status === Project::PUBLISHED || $project->status === Project::MODERATION) {
            return false;
        }

        $hackathon = Hackathon::findOrFail($project->hackathon_id);
        if ($hackathon->work_time_start > now() && $hackathon->work_time_end < now()) {
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
        $hackathon = Hackathon::findOrFail($project->hackathon_id);
        if ($hackathon->work_time_start > now() && $hackathon->work_time_end < now()) {
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
        return $user->isAdmin();
    }
}
