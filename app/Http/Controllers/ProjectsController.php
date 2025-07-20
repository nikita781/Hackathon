<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Hackathon;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProjectsController extends Controller
{
    public function index(Request $request, Hackathon $hackathon): JsonResponse
    {
        return response()->json([
            'projects' => ProjectResource::collection($hackathon->allProjects()->filter($request)->get()),
        ]);
    }

    public function store(StoreProjectRequest $request, Hackathon $hackathon, Team $team): JsonResponse
    {
        $data = Arr::except($request->validated(), ['preview']);
        $data['hackathon_id'] = $hackathon->id;
        $data['slug'] = Project::generateUniqueSlug($data['title']);
        $project = $team->projects()->create($data);

        if ($request->hasFile('preview')) {
            $project->addMediaFromRequest('preview')->toMediaCollection('preview');
        }

        return response()->json([
            'status' => 'success',
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'slug' => $project->slug,
            ],
            'message' => "Проект '". $project->title ."' успешно создан",
        ]);
    }

    public function show(Project $project)
    {
    }

    public function update(Request $request, Project $project)
    {
    }

    public function destroy(Project $project)
    {
    }
}
