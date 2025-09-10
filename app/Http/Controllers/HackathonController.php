<?php

namespace App\Http\Controllers;

use App\Exports\HackathonUsersExport;
use App\Http\Requests\HackathonRequest;
use App\Http\Requests\HackathonUpdateRequest;
use App\Http\Resources\AwardResource;
use App\Http\Resources\HackathonResource;
use App\Http\Resources\PositionResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SupportResource;
use App\Http\Resources\TabResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Support;
use App\Models\Tab;
use App\Models\Tag;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HackathonController extends Controller
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $perPage = min($request->get('per_page', 6), 10);

        $hackathons = Hackathon::filter($request)
            ->with('tags')
            ->where('status', Hackathon::STATUS_PUBLISHED)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Hackathon/Index', [
            'hackathons' => HackathonResource::collection($hackathons),
            'tags' => TagResource::collection(Tag::orderBy('title')->get()),
            'can' => [
                'create' => Gate::check('create', Hackathon::class),
            ],
            'filters'    => $request->only(
                'q', 'format', 'type', 'status', 'tags', 'order'
            ),
        ]);
    }

    public function myHackathons(Request $request): Response
    {

        $user = auth()->user();
        $perPage = min($request->get('per_page', 6), 10);

        $hackathonIds = $user->hackathons()->select('hackathons.id');

        if ($user->hasRole(Role::ORGANIZER)) {
            $hackathonIds->union(
                $user->hackathonsAsOrganizer()->select('hackathons.id')->getQuery()
            );
        }

        $upcoming = Hackathon::query()
            ->whereIn('id', $hackathonIds)
            ->filter($request)
            ->where('event_start', '>', now())
            ->with('tags')
            ->orderBy('event_start')
            ->paginate($perPage)
            ->withQueryString();

        $past = Hackathon::query()
            ->whereIn('id', $hackathonIds)
            ->filter($request)
            ->where('event_start', '<=', now())
            ->with('tags')
            ->orderByDesc('event_start')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('MyHackathon/Index', [
            'user' => $user->load('roles'),
            'upcomingHackathons' => HackathonResource::collection($upcoming),
            'pastHackathons' => HackathonResource::collection($past),
            'tags' => TagResource::collection(Tag::orderBy('title')->get()),
            'can' => [
                'create' => $user->can('create', Hackathon::class),
            ],
            'query'    => $request->only(
                'q', 'order', 'tab'
            ),
        ]);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function store(HackathonRequest $request): JsonResponse
    {
        if (!Gate::check('create', Hackathon::class)) {
            abort(404);
        }

        $data = Arr::except($request->validated(), ['tags', 'image_path']);
        $data['slug'] = Hackathon::generateUniqueSlug($data['title']);
        $user = auth()->user();
        $hackathon = $user->hackathonsAsOrganizer()->create($data);
        if ($request->hasFile('image_path')) {
            if ($hackathon->hasMedia('main_image')) {
                $hackathon->clearMediaCollection('main_image');
            }
            $hackathon->addMediaFromRequest('image_path')->toMediaCollection('main_image');
        }
        $hackathon->tags()->sync($request->tags);

        foreach (Tab::defaultStructure() as $tabTitle => $sections) {
            $tab = $hackathon->tabs()->create(['title' => $tabTitle]);

            foreach ($sections as $sectionTitle) {
                $tab->sections()->create(['title' => $sectionTitle]);
            }
        }

        return response()->json([
            'status' => 'success',
            'hackathon' => [
                'id' => $hackathon->id,
                'title' => $hackathon->title,
                'slug' => $hackathon->slug,
            ],
            'message' => "Хакатон '". $hackathon->title ."' успешно создан",
        ]);
    }

    /**
     * @param  Hackathon  $hackathon
     * @return JsonResponse|Response
     */
    public function show(Request $request, Hackathon $hackathon): JsonResponse|Response
    {
        if (!Gate::check('view', [$hackathon])) {
            abort(404);
        }
        $hackathon->load([
            'tags',
            'awards',
            'allProjects.team.teamUsers.position',
            'allProjects.team.teamUsers.user',
            'nominations.distribution',
            'criteriaGroups.criteria',
            'support.messages.user',
        ]);

        $perPageProject = min($request->get('per_page', 6), 10);
        $perPageTeam = min($request->get('per_page', 6), 10);
        $user = auth()->user();
        $isStaffHackathon = $user?->isHackathonStaff($hackathon);

        $teams = collect();
        $ownTeam = null;

        if (!isset($user)) {
            $teams = collect();
        } else if ($isStaffHackathon) {
            $teams = $hackathon->teams()
                ->with(['projects', 'teamUsers.user', 'teamUsers.position'])
                ->filter($request)
                ->paginate($perPageTeam);
        } else {
            $ownTeam = $hackathon->ownTeam($user);
        }

        $tabs = $hackathon->tabs()->with(['sections.items', 'media', 'hackathon'])->get();

        $positions = Position::getAllPositionExceptCapitan();

        $hackathonResource = new HackathonResource($hackathon);
        $tabsResource = TabResource::collection($tabs)->additional(['hackathon' => $hackathon->id]);
        $teamsResource = $teams->isNotEmpty() ? TeamResource::collection($teams) : null;
        $ownTeamResource = $ownTeam ? new TeamResource($ownTeam) : null;
        $allProjects = $teams->isNotEmpty() ? ProjectResource::collection($hackathon->allProjects()->paginate($perPageProject)) : null;
        $positionsResource = PositionResource::collection($positions);
        $hackathonStaff = UserResource::collection($hackathon->getAllHackathonStaff());
        $supports = SupportResource::collection($hackathon->support()->where('type', Support::QUESTION)->orWhere('type', Support::SUGGESTION)->orderBy('created_at')->with('messages.user')->get());
        $tags = TagResource::collection(Tag::all());

        if ($request->wantsJson()) {
            return response()->json([
                "hackathon" => $hackathonResource->response(),
                "tabs" => $tabsResource->response(),
                "teams" => optional($teamsResource)->response(),
                "ownTeam" => optional($ownTeamResource)->response(),
                "allProjects" => optional($allProjects)->response(),
                "positions" => $positionsResource->response(),
            ]);
        }

        return Inertia::render('Hackathon/Show', [
            'hackathon' => $hackathonResource,
            'tabs' => $tabsResource,
            'teams' => $teamsResource,
            'ownTeam' => $ownTeamResource,
            'positions' => $positionsResource,
            'hackathonStaff' => $hackathonStaff,
            'allProjects' => $allProjects,
            'supports' => $supports,
            'is_join' => $user ? $user->onHackathonAsMember($hackathon) : false,
            'tags' => $tags,
            'can' => [
                'hackathon' => [
                    'join' => Gate::check('join', $hackathon),
                    'update' => Gate::check('update', $hackathon),
                    'delete' => Gate::check('delete', $hackathon),
                    'viewTask' => Gate::check('viewTask', $hackathon),
                    'rate' => Gate::check('evaluation', $hackathon),
                    'moderate' => Gate::check('moderate', Hackathon::class)
                ],
                'team' => [
                    'update' => Gate::check('update', $ownTeam),
                    'kick' => Gate::check('kick', $ownTeam),
                    'joinTeam' => Gate::check('joinTeam', $ownTeam),
                    'invite' => Gate::check('invite', $ownTeam),
                ],
                'project' => [
                    'viewAll' => Gate::check('viewAll', [Project::class, $hackathon]),
                    'createProject' => Gate::check('createProject', [Project::class, $hackathon]),
                    'moderate' => Gate::check('moderate', Project::class)
                ],
                'support' => [
                    'viewAny' => Gate::check('viewAny', [Support::class, $hackathon]),
                    'create' => Gate::check('createSupport', [Support::class, $hackathon]),
                    'answer' => $isStaffHackathon,
                ],
            ],
        ]);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function update(HackathonUpdateRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $data = Arr::except($request->validated(), ['tags', 'image_path']);

        if ($hackathon->status !== Hackathon::STATUS_DRAFT) {
            $data['type'] = $hackathon->type;
            $data['min_team_size'] = $hackathon->min_team_size;
            $data['max_team_size'] = $hackathon->max_team_size;
        } elseif (($data['type'] ?? $hackathon->type) === 'individual') {
            $data['min_team_size'] = 1;
            $data['max_team_size'] = 1;
        }

        $hackathon->update($data);
        $hackathon->refresh();
        if ($request->hasFile('image_path')) {
            if ($hackathon->hasMedia('main_image')) {
                $hackathon->clearMediaCollection('main_image');
            }
            $hackathon->addMediaFromRequest('image_path')->toMediaCollection('main_image');
        }
        if (isset($request->tags)) {
            $hackathon->tags()->sync($request->tags);
        }
        return back()->with('status', 'Хакатон обновлен');
    }

    public function publish(Hackathon $hackathon): RedirectResponse
    {
        Gate::authorize('publish', $hackathon);

        $hackathon->update([
            'status' => Hackathon::STATUS_MODERATION,
            'moderated_time' => Carbon::now(),
        ]);

        return back()->with('status', 'Хакатон отправлен на модерацию');
    }

    public function destroy(Hackathon $hackathon): void
    {
    }

    public function joinHackathon(Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('join', $hackathon)) {
            abort(404);
        }

        $user = auth()->user();

        $user->hackathons()->attach($hackathon->id, ['role_id' => Role::MEMBER]);

        $team = $hackathon->teams()->create([
            'title' => "Команда {$user->name}"
        ]);

        $user->teams()->syncWithoutDetaching([
            $team->id => ['position_id' => Position::CAPITAN_POSITION]
        ]);

        return back()->with('status', 'Вы успешно присоединились к хакатону!');
    }

    public function leaveHackathon(Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('leave', $hackathon)) {
            abort(404);
        }

        $user = auth()->user();

        $user->hackathons()->detach($hackathon->id);

        return back()->with('status', 'Вы успешно покинули хакатон!');
    }

    public function downloadUsers(Hackathon $hackathon): BinaryFileResponse
    {
        $users = $hackathon->members()->with('teams.projects')->get();

        $rows = collect();
        $total = $users->count();
        $submitted = 0;

        $rows->push([
            'nickname' => 'Участник',
            'status'   => 'Статус',
        ]);

        foreach ($users as $user) {
            $hasProject = $user->teams()
                ->where('hackathon_id', $hackathon->id)
                ->whereHas('projects', fn($q) => $q->where('status', Project::PUBLISHED))
                ->exists();

            if ($hasProject) {
                $submitted++;
            }

            $rows->push([
                'nickname' => $user->nickname,
                'status' => $hasProject ? '✅ Сдал' : '❌ Не сдал',

            ]);
        }

        $percent = $total > 0 ? round($submitted / $total * 100) : 0;

        $rows->prepend([
            'nickname' => 'Сдали / Всего',
            'status'   => "$submitted / $total ($percent%)",
        ]);

        return Excel::download(new HackathonUsersExport($rows), "hackathon_users_{$hackathon->slug}.xlsx");
    }

    public function gallery(Request $request, Hackathon $hackathon): JsonResponse
    {
        return response()->json([
            'gallery' => $hackathon->allProjects()->filter($request)->published()->get()
        ]);
    }
}
