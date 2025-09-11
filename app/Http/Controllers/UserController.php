<?php

namespace App\Http\Controllers;

use App\Http\Resources\AwardResource;
use App\Models\Project;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function show(User $user): Response
    {
        $projects = Project::whereIn('team_id', $user->teams()->pluck('teams.id'))
            ->where('status', Project::PUBLISHED)
            ->with('hackathon', 'team')
            ->get();

        $projectsData = $projects->map(function ($project) {
            return [
                'slug' => $project->slug,
                'title' => $project->title,
                'description' => $project->description,
                'place' =>$project->team->place,
                'hackathon' => [
                    'slug' => $project->hackathon->slug,
                    'title' => $project->hackathon->title,
                ],
                'certificate_url' => route('hackathons.certificate', ['hackathon' => $project->hackathon->slug]),
            ];
        });

        return Inertia::render('Dashboard', [
            'user' => $user->load('roles'),
            'awards' => AwardResource::collection($user->awards()->withPivot('awarded_at')->get()),
            'projects' => $projectsData,
        ]);
    }

    public function showMe(): Response
    {
        return $this->show(auth()->user());
    }
}
