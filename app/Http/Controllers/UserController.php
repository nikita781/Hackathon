<?php

namespace App\Http\Controllers;

use App\Http\Resources\AwardResource;
use App\Http\Resources\UserProjectsResource;
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
            ->with('hackathon', 'team');

        $projects = UserProjectsResource::collection($projectsQuery->paginate($perPage)->withQueryString());

        return Inertia::render('Dashboard', [
            'user' => $user->load('roles'),
            'awards' => AwardResource::collection($user->awards()->withPivot('awarded_at')->get()),
            'projects' => $projects,
        ]);
    }

    public function showMe(): Response
    {
        return $this->show(auth()->user());
    }
}
