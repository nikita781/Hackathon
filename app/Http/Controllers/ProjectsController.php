<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\Hackathon;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    public function index(Request $request, Hackathon $hackathon): JsonResponse
    {
        return response()->json([
            'projects' => ProjectResource::collection($hackathon->allProjects()->filter($request)->get()),
        ]);
    }

    public function store(Request $request)
    {
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
