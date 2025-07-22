<?php

namespace App\Http\Controllers;

use App\Http\Requests\HackathonRequest;
use App\Http\Requests\HackathonUpdateRequest;
use App\Http\Resources\AwardResource;
use App\Http\Resources\HackathonResource;
use App\Http\Resources\PositionResource;
use App\Http\Resources\TabResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TeamResource;
use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Tab;
use App\Models\Tag;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
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
        $hackathons = Hackathon::filter($request)
            ->with('tags')
            ->where('is_published', true)
            ->latest()
            ->paginate($request->per_page ?? 6)
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
        $perPage = $request->per_page ?? 6;

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
            'nominations.distribution',
            'criteriaGroups.criteria',
        ]);

        $user = auth()->user();

        $teams = collect();
        $ownTeam = null;

        if (!isset($user)) {
            $teams = collect();
        } else if ($user->isHackathonStaff($hackathon)) {
            $teams = $hackathon->teams()
                ->with(['projects', 'users.positions'])
                ->get();
        } else {
            $ownTeam = $hackathon->ownTeam($user);
        }

        $tabs = $hackathon->tabs()->with(['sections.items', 'media'])->get();

        $positions = Position::getAllPositionExceptCapitan();

        $hackathonResource = new HackathonResource($hackathon);
        $tabsResource = TabResource::collection($tabs)->additional(['hackathon' => $hackathon->id]);
        $teamsResource = $teams->isNotEmpty() ? TeamResource::collection($teams) : null;
        $ownTeamResource = $ownTeam ? new TeamResource($ownTeam) : null;
        $positionsResource = PositionResource::collection($positions);

        if ($request->wantsJson()) {
            return response()->json([
                "hackathon" => $hackathonResource->response(),
                "tabs" => $tabsResource->response(),
                "teams" => optional($teamsResource)->response(),
                "ownTeam" => optional($ownTeamResource)->response(),
                "positions" => $positionsResource->response(),
            ]);
        }

        return Inertia::render('Hackathon/Show', [
            'hackathon' => $hackathonResource,
            'tabs' => $tabsResource,
            'teams' => $teamsResource,
            'ownTeam' => $ownTeamResource,
            'positions' => $positionsResource,
            'is_join' => $user ? $user->onHackathonAsMember($hackathon) : false,
            'can' => [
                'hackathon' => [
                    'join' => Gate::check('join', $hackathon),
                    'update' => Gate::check('update', $hackathon),
                    'delete' => Gate::check('delete', $hackathon),
                    'viewTask' => Gate::check('viewTask', $hackathon),
                ],
                'team' => [
                    'update' => Gate::check('update', $ownTeam),
                    'kick' => Gate::check('kick', $ownTeam),
                    'joinTeam' => Gate::check('joinTeam', $ownTeam),
                    'invite' => Gate::check('invite', $ownTeam),
                ],
                'project' => [
                    'viewAny' => Gate::check('viewAny', Project::class),
                    'createProject' => Gate::check('createProject', [Project::class, $hackathon]),
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

        if ($hackathon->is_published) {
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
}
