<?php

namespace App\Policies;

use App\Models\Hackathon;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Project $project): bool
    {
        if ($project->status === Project::PUBLISHED) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->isHackathonStaff(Hackathon::find($project->hackathon_id))) {
            return true;
        }

        return $user->isMemberOfProject($project);
    }

    public function createProject(User $user, Hackathon $hackathon): bool
    {
        return $user->isCapitanOfHackathon($hackathon);
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isHackathonStaff(Hackathon::find($project->hackathon_id))) {
            return true;
        }

        if ($project->status === Project::PUBLISHED || $project->status === Project::MODERATION) {
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
}
