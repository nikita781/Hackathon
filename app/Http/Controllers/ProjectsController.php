<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Evaluation;
use App\Models\Hackathon;
use App\Models\Project;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
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

    public function showTeamProjects(Request $request, Hackathon $hackathon, Team $team): JsonResponse
    {
        if (!$request->wantsJson()) {
            abort(404);
        }

        return response()->json([
            'projects' => ProjectResource::collection($team->projects()->with(['team.teamUsers.position', 'team.teamUsers.user'])->get()),
        ]);
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     * @param \Illuminate\Http\Request|\App\Http\Requests\StoreProjectRequest $request
     */
    public function store(StoreProjectRequest $request, Hackathon $hackathon, Team $team): JsonResponse
    {
        if (!Gate::check('createProject', [Project::class, $hackathon])) {
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
                'description' => $project->description,
                'slug' => $project->slug,
            ],
            'message' => "Проект '" . $project->title . "' успешно создан",
        ]);
    }

    public function show(Hackathon $hackathon, Project $project): JsonResponse
    {
        if (!Gate::check('view', $project)) {
            abort(404);
        }

        $project->load('team.teamUsers.user', 'team.teamUsers.position');

        $groupCriteries = null;

        if ($hackathon->is_finished) {
            $groupCriteries = $hackathon->criteriaGroups()->with([
                'criteria.evaluations' => function($q) use($project) {
                    $q->where("evaluations.project_id", $project->id);
                }
            ])->get();
        }

        return response()->json([
            'project' => new ProjectResource($project),
            'groupCriteries' => $groupCriteries,
        ]);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws MediaCannotBeDeleted
     * @param \Illuminate\Http\Request|\App\Http\Requests\UpdateProjectRequest $request
     */
    public function update(UpdateProjectRequest $request, Hackathon $hackathon, Project $project): RedirectResponse
    {
        if (!Gate::check('update', $project)) {
            abort(ResponseAlias::HTTP_FORBIDDEN, 'У вас нет прав для обновления проекта');
        }

        $data = Arr::except($request->validated(), ['preview', 'presentation', 'gallery', 'delete_media_ids']);
        $gallery = $request->validated('gallery');

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

        if (!empty($gallery)) {
            foreach ($gallery as $galleryImage) {
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
        if (!Gate::check('delete', $project)) {
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

    public function publish(Hackathon $hackathon, Team $team, Project $project)
    {
        if (!Gate::check('publish', $project)) {
            throw ValidationException::withMessages([
                'project' => 'Вы не можете опубликовать проект',
            ]);
        }

        if ($team->users_count > $hackathon->max_team_size || $team->users_count < $hackathon->min_team_size) {
            throw ValidationException::withMessages([
                'team' => 'Ваша команда не подходит под критерии хакатона',
            ]);
        }

        $project->update([
            'status' => Project::MODERATION,
            'moderated_time' => Carbon::now()
        ]);

        return back()->with('status', 'Проект отправлен на модерацию!');
    }

    public function rate(Request $request, Hackathon $hackathon, Project $project): RedirectResponse
    {
        if (!Gate::check('evaluation', $hackathon)) {
            abort(403);
        }

        $data = $request->validate([
            'evaluations' => ['required', 'array'],
            'evaluations.*.criterion_id' => ['required', 'exists:criteria,id'],
            'evaluations.*.score' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['evaluations'] as $evaluation) {
            Evaluation::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'project_id' => $project->id,
                    'criterion_id' => $evaluation['criterion_id'],
                ],
                [
                    'score' => $evaluation['score'],
                ]
            );
        }

        $project->updateAvgScore();

        return back()->with('status', 'Оценки сохранены');
    }

    public function deletePresentation(Hackathon $hackathon, Project $project): RedirectResponse
    {
        if (!Gate::check('update', $project)) {
            abort(403);
        }

        if ($project->hasMedia('presentation')) {
            $project->clearMediaCollection('presentation');
        }

        return back()->with('status', 'Презентация успешно удалена!');
    }
}
