<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Hackathon;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MediaCannotBeDeleted;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ProjectsController extends Controller
{
    public function index(Request $request, Hackathon $hackathon): JsonResponse
    {
        if (!Gate::check('viewAll', [Project::class, $hackathon])) {
            abort('404', 'У вас нет прав для просмотра проектов');
        }

        return response()->json([
            'projects' => ProjectResource::collection($hackathon->allProjects()->filter($request)->get()),
        ]);
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function store(StoreProjectRequest $request, Hackathon $hackathon, Team $team): JsonResponse
    {
        if(!Gate::check('createProject', Project::class)) {
            abort(ResponseAlias::HTTP_FORBIDDEN, 'У вас нет прав для создания проекта');
        }

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

    public function show(Project $project): JsonResponse
    {
        if (!Gate::check('view', $project)) {
            abort(404);
        }

        $project->load('team.teamUsers.user', 'team.teamUsers.position');
        return response()->json([
            'project' => new ProjectResource($project)
        ]);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws MediaCannotBeDeleted
     */
    public function update(UpdateProjectRequest $request, Hackathon $hackathon, Project $project): RedirectResponse
    {
        if(!Gate::check('update', Project::class)) {
            abort(ResponseAlias::HTTP_FORBIDDEN, 'У вас нет прав для обновления проекта');
        }

        $data = Arr::except($request->validated(), ['preview', 'presentation', 'gallery', 'delete_media_ids']);

        if ($request->hasFile('preview')) {
            if ($project->hasMedia('preview')) {
                $project->clearMediaCollection('preview');
            }
            $project->addMediaFromRequest('preview')->toMediaCollection('preview');
        }

        if ($request->hasFile('presentation')) {
            if ($project->hasMedia('presentation')) {
                $project->clearMediaCollection('presentation');
            }
            $project->addMediaFromRequest('presentation')->toMediaCollection('presentation');
        }

        if ($request->has('galley')) {
            foreach ($request->get('gallery') as $galleryImage) {
                if ($project->hasMedia('gallery')) {
                    $project->clearMediaCollection('gallery');
                }
                $project->addMedia($galleryImage)->toMediaCollection('gallery');
            }
        }

        if (!empty($request->get('delete_media_ids'))) {
            foreach ($request->get('delete_media_ids') as $id) {
                $project->deleteMedia($id);
            }
        }

        $project->update($data);

        return back()->with('status', 'Проект успешно обновлен!');
    }

    public function destroy(Hackathon $hackathon, Project $project): RedirectResponse
    {
        if(!Gate::check('delete', $project)) {
            abort(ResponseAlias::HTTP_FORBIDDEN, 'У вас нет прав для удаления проекта');
        }

        if ($project->hasMedia('preview')) {
            $project->clearMediaCollection('preview');
        }

        if ($project->hasMedia('presentation')) {
            $project->clearMediaCollection('presentation');
        }

        if ($project->hasMedia('gallery')) {
            $project->clearMediaCollection('gallery');
        }

        $project->delete();

        return back()->with('status', 'Проект успешно удален!');
    }
}
