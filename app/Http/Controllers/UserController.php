<?php

namespace App\Http\Controllers;

use App\Http\Resources\AwardResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserProjectsResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function show(User $user): Response
    {
        $perPage = 8;

        $projectsQuery = Project::whereIn('team_id', $user->teams()->pluck('teams.id'))
            ->where('status', Project::PUBLISHED)
            ->whereHas('hackathon', function ($q) {
                $q->where('is_finished', true);
            })
            ->with('hackathon', 'team', 'team.teamUsers.user', 'team.teamUsers.position');

        $projects = UserProjectsResource::collection($projectsQuery->paginate($perPage)->withQueryString());

        $user->load('roles');
        $teamRelations = ['owner', 'teamUsers.user', 'teamUsers.position'];

        $createdTeams = $user
            ->ownedProfileTeams()
            ->with($teamRelations)
            ->latest()
            ->get();

        $memberTeams = $user
            ->profileTeams()
            ->where(function ($query) use ($user) {
                $query
                    ->whereNull('teams.owner_id')
                    ->orWhere('teams.owner_id', '!=', $user->id);
            })
            ->with($teamRelations)
            ->latest('teams.created_at')
            ->get();

        return Inertia::render('Dashboard', [
            'user' => new UserResource($user),
            'awards' => AwardResource::collection($user->awards()->withPivot('awarded_at')->get()),
            'projects' => $projects,
            'createdTeams' => TeamResource::collection($createdTeams),
            'memberTeams' => TeamResource::collection($memberTeams),
        ]);
    }

    public function showMe(): Response
    {
        return $this->show(auth()->user());
    }
}
