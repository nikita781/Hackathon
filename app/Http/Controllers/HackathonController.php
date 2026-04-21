<?php

namespace App\Http\Controllers;

use App\Actions\FinishOneHackathon;
use App\Exports\HackathonUsersExport;
use App\Http\Requests\HackathonRequest;
use App\Http\Requests\HackathonUpdateRequest;
use App\Http\Resources\HackathonResource;
use App\Http\Resources\PositionResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SupportResource;
use App\Http\Resources\TabResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Banner;
use App\Models\Hackathon;
use App\Models\HackathonInvite;
use App\Models\HackathonUserRequest;
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Support;
use App\Models\Tab;
use App\Models\Tag;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use App\Notifications\InviteNotification;
use App\Notifications\ModerateNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Mews\Purifier\Facades\Purifier;
use Mustache\Engine;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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

        $banners = Banner::query()
            ->orderBy('order')
            ->get();

        return Inertia::render('Hackathon/Index', [
            'banners' => $banners,
            'hackathons' => HackathonResource::collection($hackathons),
            'tags' => TagResource::collection(Tag::orderBy('order')->get()),
            'can' => [
                'create' => Gate::check('create', Hackathon::class),
            ],
            'filters' => $request->only(
                'q',
                'format',
                'type',
                'status',
                'tags',
                'order'
            ),
        ]);
    }

    public function myHackathons(Request $request): Response
    {
        $user = auth()->user();
        if (false) {
        $data = $request->validate([
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $profileTeam = null;
        if (!empty($data['team_id'])) {
            $profileTeam = $user->teams()
                ->where('teams.id', $data['team_id'])
                ->whereNull('teams.hackathon_id')
                ->wherePivot('position_id', Position::CAPITAN_POSITION)
                ->with(['teamUsers.user.roles', 'teamUsers.position'])
                ->first();

            if (! $profileTeam) {
                throw ValidationException::withMessages([
                    'team_id' => ['Команда для вступления не найдена или недоступна.'],
                ]);
            }
        }
        }
        $perPage = min($request->get('per_page', 6), 10);

        $hackathonIds = $user->hackathons()->pluck('hackathons.id')->toArray();

        if ($user->hasRole(Role::ORGANIZER)) {
            $hackathonIds = array_merge(
                $hackathonIds,
                $user->hackathonsAsOrganizer()->pluck('hackathons.id')->toArray()
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

        $continue = Hackathon::query()
            ->whereIn('id', $hackathonIds)
            ->filter($request)
            ->where('event_start', '<', now())
            ->where('event_end', '>', now())
            ->with('tags')
            ->orderByDesc('event_end')
            ->paginate($perPage)
            ->withQueryString();

        $past = Hackathon::query()
            ->whereIn('id', $hackathonIds)
            ->filter($request)
            ->where('event_end', '<', now())
            ->with('tags')
            ->orderByDesc('event_end')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('MyHackathon/Index', [
            'user' => $user->load('roles'),
            'upcomingHackathons' => HackathonResource::collection($upcoming),
            'continueHackathons' => HackathonResource::collection($continue),
            'pastHackathons' => HackathonResource::collection($past),
            'tags' => TagResource::collection(Tag::orderBy('title')->get()),
            'can' => [
                'create' => $user->can('create', Hackathon::class),
            ],
            'query' => $request->only(
                'q',
                'order',
                'tab'
            ),
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request|\App\Http\Requests\HackathonRequest  $request
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function store(HackathonRequest $request): JsonResponse
    {
        if (!Gate::check('create', Hackathon::class)) {
            abort(404);
        }

        $data = Arr::except($request->validated(), ['tags', 'image_path']);
        $data['slug'] = Hackathon::generateUniqueSlug($data['title']);
        $data['registration_start'] = Carbon::now()->toDateTimeString();
        $data['locale'] = app()->getLocale();
        $user = auth()->user();
        $hackathon = $user->hackathonsAsOrganizer()->create($data);
        if ($request->hasFile('image_path')) {
            if ($hackathon->hasMedia('main_image')) {
                $hackathon->clearMediaCollection('main_image');
            }
            $hackathon->addMediaFromRequest('image_path')->toMediaCollection('main_image');
        }
        $hackathon->tags()->sync($request->input('tags'));

        foreach (Tab::defaultStructure() as $tabTitle => $sections) {
            $tabTranslations = Tab::DEFAULT_TRANSLATIONS[$tabTitle] ?? [];

            $tab = $hackathon->tabs()->create([
                'title' => $tabTitle
            ]);

            foreach ($sections as $sectionTitle) {
                $sectionTranslations = $tabTranslations['sections'][$sectionTitle]['title'] ?? [];

                $nestedTranslations = [];
                foreach ($sectionTranslations as $lang => $translation) {
                    $nestedTranslations[$lang] = ['title' => $translation];
                }

                $tab->sections()->createQuietly([
                    'title' => $sectionTitle,
                    'translations' => $nestedTranslations,
                    'locale' => 'ru'
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'hackathon' => [
                'id' => $hackathon->id,
                'title' => $hackathon->title,
                'slug' => $hackathon->slug,
            ],
            'message' => __('hackathon_created_success'),
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
            'criteriaGroups' => function ($q) {
                $q->with(['criteria' => function ($q) {
                    $q->orderBy('id')->with('evaluations');
                }]);
            },
            'support.messages.user',
            'requests' => function ($q) {
                $q->with(['user' => function ($q) {
                    $q->orderBy('id');
                }])->orderBy('id');
            },
        ]);

        $perPageProject = min($request->get('per_page', 6), 10);
        $perPageTeam = min($request->get('per_page', 6), 10);
        $user = auth()->user();
        $isStaffHackathon = $user?->isHackathonStaff($hackathon) || $user?->isAdmin();

        $teams = collect();
        $ownTeam = null;

        if (!isset($user)) {
            $teams = collect();
        } else {
            if ($isStaffHackathon) {
                $teams = $hackathon
                    ->teams()
                    ->with(['projects', 'teamUsers.user', 'teamUsers.position'])
                    ->filter($request)
                    ->paginate($perPageTeam);
            } else {
                $ownTeam = $hackathon->ownTeam($user);
            }
        }

        $tabs = $hackathon->tabs()->with(['sections.items', 'media', 'hackathon'])->get();

        $positions = Position::getAllPositionExceptCapitan();

        $hackathonResource = new HackathonResource($hackathon);
        $tabsResource = TabResource::collection($tabs)->additional(['hackathon' => $hackathon->id]);
        $ownTeamResource = $ownTeam ? new TeamResource($ownTeam) : null;
        $allProjects = $teams->isNotEmpty() ? ProjectResource::collection($hackathon->allProjects()->paginate($perPageProject)) : null;
        $positionsResource = PositionResource::collection($positions);
        $hackathonStaff = UserResource::collection($hackathon->getAllHackathonStaff());
        $supports = SupportResource::collection($hackathon->support()->where('type', Support::QUESTION)->orWhere('type',
            Support::SUGGESTION)->orderBy('created_at')->with('messages.user')->get());
        $tags = TagResource::collection(Tag::all());
        $availableProfileTeams = collect();

        if ($user && ! $user->onHackathonAsMember($hackathon)) {
            $captainProfileTeams = $user->teams()
                ->whereNull('teams.hackathon_id')
                ->wherePivot('position_id', Position::CAPITAN_POSITION)
                ->with(['owner', 'teamUsers.user.roles', 'teamUsers.position'])
                ->latest('teams.created_at')
                ->get();

            $availableProfileTeams = $captainProfileTeams->map(function (Team $team) use ($request, $hackathon) {
                return array_merge(
                    (new TeamResource($team))->toArray($request),
                    $this->getProfileTeamJoinAvailability($team, $hackathon)
                );
            })->values();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'hackathon' => $hackathonResource,
                'tabs' => $tabsResource->response(),
                'ownTeam' => optional($ownTeamResource)->response(),
                'allProjects' => optional($allProjects)->response(),
                'positions' => $positionsResource->response(),
                'availableProfileTeams' => $availableProfileTeams,
            ]);
        }

        return Inertia::render('Hackathon/Show', [
            'hackathon' => $hackathonResource,
            'tabs' => $tabsResource,
            'ownTeam' => $ownTeamResource,
            'positions' => $positionsResource,
            'hackathonStaff' => $hackathonStaff,
            'allProjects' => $allProjects,
            'supports' => $supports,
            'is_join' => $user ? $user->onHackathonAsMember($hackathon) : false,
            'availableProfileTeams' => $availableProfileTeams,
            'tags' => $tags,
            'can' => [
                'hackathon' => [
                    'join' => Gate::check('join', $hackathon),
                    'update' => Gate::check('update', $hackathon),
                    'update_published' => Gate::check('updatePublished', $hackathon),
                    'delete' => Gate::check('delete', $hackathon),
                    'finish' => Gate::check('finish', $hackathon),
                    'viewTask' => Gate::check('viewTask', $hackathon),
                    'rate' => Gate::check('evaluation', $hackathon),
                    'publish' => Gate::check('publish', $hackathon),
                    'downloadProtocol' => Gate::check('downloadReport', $hackathon),
                    'moderate' => Gate::check('moderate', Hackathon::class),
                    'viewSupport' => Gate::check('viewAny', [Support::class, $hackathon]),
                    'is_staff' => $isStaffHackathon,
                    'leave' => Gate::check('leave', $hackathon),
                    'approve' => Gate::check('approve', $hackathon),
                ],
                'team' => [
                    'view' => Gate::check('view', $ownTeam),
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
     * @param  \Illuminate\Http\Request|\App\Http\Requests\HackathonUpdateRequest  $request
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
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

        $data['locale'] = app()->getLocale();

        $hackathon->update($data);
        $hackathon->refresh();
        if ($request->hasFile('image_path')) {
            if ($hackathon->hasMedia('main_image')) {
                $hackathon->clearMediaCollection('main_image');
            }
            $hackathon->addMediaFromRequest('image_path')->toMediaCollection('main_image');
        }
        if ($request->filled('tags')) {
            $hackathon->tags()->sync($request->input('tags'));
        }
        return back()->with('status', __('hackathon_updated_success'));
    }

    public function publish(Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('publish', $hackathon)) {
            return back()->with('error', __('cannot_publish_hackathon'));
        }

        if ($hackathon->work_time_start === null || $hackathon->work_time_end === null || $hackathon->evaluation_start === null || $hackathon->evaluation_end === null) {
            return back()->with('error', __('dates_required_for_publish'));
        }

        if ($hackathon->registration_end < now()) {
            return back()->with('error', __('registration_end_date_passed'));
        }

        if (!$hackathon->criteriaGroups()->has('criteria')->exists()) {
            return back()->with('error', __('criteria_required_for_publish'));
        }

        if (!$hackathon->users()->wherePivot('role_id', Role::JUDGE)->exists()) {
            return back()->with('error', __('judge_required_for_publish'));
        }

        $hackathon->update([
            'status' => Hackathon::STATUS_MODERATION,
            'moderated_time' => Carbon::now(),
        ]);

        return back()->with('status', __('hackathon_sent_to_moderation'));
    }

    public function destroy(Hackathon $hackathon): void
    {
    }

    public function joinHackathon(Request $request, Hackathon $hackathon): JsonResponse|RedirectResponse
    {
        $this->abortIfTeamReadOnly();

        if (!Gate::check('join', $hackathon)) {
            abort(403);
        }

        $user = auth()->user();
        $data = $request->validate([
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $profileTeam = null;
        if (!empty($data['team_id'])) {
            $profileTeam = $user->teams()
                ->where('teams.id', $data['team_id'])
                ->whereNull('teams.hackathon_id')
                ->wherePivot('position_id', Position::CAPITAN_POSITION)
                ->with(['teamUsers.user.roles', 'teamUsers.position'])
                ->first();

            if (! $profileTeam) {
                throw ValidationException::withMessages([
                    'team_id' => ['Команда для вступления не найдена или недоступна.'],
                ]);
            }
        }

        if ($hackathon->isModeration()) {
            if ($profileTeam) {
                throw ValidationException::withMessages([
                    'team_id' => ['Для хакатонов с модерацией вступление готовой командой пока недоступно.'],
                ]);
            }

            $reqExist = HackathonUserRequest::query()
                ->where('hackathon_id', $hackathon->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($reqExist) {
                return $this->membershipResponse($request, 'error', 'Вы уже отправили запрос на вступление, ожидайте', [
                    'joined' => false,
                    'requested' => true,
                ], 422);
            }

            HackathonUserRequest::create([
                'hackathon_id' => $hackathon->id,
                'user_id' => $user->id,
                'status' => HackathonUserRequest::STATUS_PENDING,
            ]);

            return $this->membershipResponse($request, 'status', 'Запрос на вступление отправлен на модерацию', [
                'joined' => false,
                'requested' => true,
            ]);
        }

        if ($profileTeam) {
            $availability = $this->getProfileTeamJoinAvailability($profileTeam, $hackathon);

            if (! $availability['can_join_hackathon']) {
                throw ValidationException::withMessages([
                    'team_id' => $availability['join_errors'],
                ]);
            }

            $this->registerProfileTeamOnHackathon($hackathon, $profileTeam);

            return $this->membershipResponse($request, 'status', __('joined_hackathon_success'), [
                'joined' => true,
            ]);
        }

        $user->hackathons()->attach($hackathon->id, ['role_id' => Role::MEMBER]);

        $team = $hackathon->teams()->create([
            'title' => __('team_title') . " " . $user->nickname
        ]);

        $user->teams()->syncWithoutDetaching([
            $team->id => ['position_id' => Position::CAPITAN_POSITION]
        ]);

        return $this->membershipResponse($request, 'status', __('joined_hackathon_success'), [
            'joined' => true,
        ]);
    }

    public function acceptUser(Hackathon $hackathon, int $userRequest): RedirectResponse
    {
        $this->abortIfTeamReadOnly();

        Gate::authorize('approve', $hackathon);

        $hackathonUserRequest = HackathonUserRequest::findOrFail($userRequest);
        $user = $hackathonUserRequest->user;

        if ($hackathonUserRequest->status === HackathonUserRequest::STATUS_REJECT) {
            return back()->with('error', __("user_already_in_hackathon", ['user_nickname' => $user->nickname]));
        }

        $user->hackathons()->attach($hackathon->id, ['role_id' => Role::MEMBER]);

        $team = $hackathon->teams()->create([
            'title' => __('team_title') . " " . $user->nickname
        ]);

        $user->teams()->syncWithoutDetaching([
            $team->id => ['position_id' => Position::CAPITAN_POSITION]
        ]);

        $hackathonUserRequest->update([
            'status' => HackathonUserRequest::STATUS_ACCEPT,
        ]);

        $user->notify(new ModerateNotification([
            'status' => 'accept',
            'description' => "",
            'title' => __('joined_hackathon_success'),
            'send_at' => now()->toDateString(),
            'hackathon' => $hackathon,
            'project' => null,
        ]));

        return back()->with('status', 'Пользователь успешно принят на хакатон');
    }

    public function rejectUser(Hackathon $hackathon, int $userRequest): RedirectResponse
    {
        Gate::authorize('approve', $hackathon);

        $hackathonUserRequest = HackathonUserRequest::findOrFail($userRequest);

        if ($hackathonUserRequest->status === HackathonUserRequest::STATUS_REJECT) {
            return back()->with('error', "Пользователь уже получил отказ"); //TODO: добавить в перевод
        }

        $user = $hackathonUserRequest->user;

        $hackathonUserRequest->update([
            'status' => HackathonUserRequest::STATUS_REJECT,
        ]);

        $user->notify(new ModerateNotification([
            'status' => 'rejected',
            'description' => "",
            'title' => "Вам отказали во вступлении в хакатон", //TODO: добавить в перевод
            'send_at' => now()->toDateString(),
            'hackathon' => $hackathon,
            'project' => null,
        ]));

        return back()->with('status', 'Пользователю успешно отказано в приеме на хакатон'); //TODO: добавить в перевод
    }

    public function leaveHackathon(Request $request, Hackathon $hackathon): JsonResponse|RedirectResponse
    {
        $this->abortIfTeamReadOnly();

        if (!Gate::check('leave', $hackathon)) {
            abort(403);
        }

        $user = auth()->user();

        $this->detachUserFromHackathon($user, $hackathon);

        return $this->membershipResponse($request, 'status', 'Вы покинули хакатон', [
            'left' => true,
        ]);
    }

    public function kickUser(Request $request, Hackathon $hackathon): RedirectResponse
    {
        $this->abortIfTeamReadOnly();

        Gate::authorize('update', $hackathon);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id']
        ]);

        $user = User::find($data['user_id']);

        if (!$hackathon->users()->where('role_id', Role::MEMBER)->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Пользователь не участвует в хакатоне'); //TODO: добавить в перевод
        }

        $this->detachUserFromHackathon($user, $hackathon);

        return back()->with('status', __('user_kicked_success'));
    }

    private function detachUserFromHackathon(User $user, Hackathon $hackathon): void
    {
        $team = $user->teams()->where('hackathon_id', $hackathon->id)->first();

        if (!$team) {
            back()->with('error', __('not_in_team'));
            return;
        }

        $membersCount = $team->users()->count();

        $isCaptain = $team->teamUsers()
            ->where('user_id', $user->id)
            ->where('position_id', Position::CAPITAN_POSITION)
            ->exists();

        if ($membersCount === 1) {
            if ($team->project) {
                $team->project->delete();
            }

            $team->delete();

            $user->hackathons()->detach($hackathon->id);

            back()->with('status', __('left_hackathon_team_deleted'));
            return;
        }

        if ($isCaptain) {
            $nextCaptain = $team->users()
                ->where('user_id', '!=', $user->id)
                ->orderBy('team_user.created_at')
                ->first();

            if ($nextCaptain) {
                $team->teamUsers()
                    ->where('user_id', $nextCaptain->id)
                    ->update(['position_id' => Position::CAPITAN_POSITION]);
            }
        }

        $team->users()->detach($user->id);

        $user->hackathons()->detach($hackathon->id);

        back()->with('status', __('left_hackathon'));
        return;
    }

    public function downloadUsers(Hackathon $hackathon): BinaryFileResponse
    {
        $users = $hackathon->members()->with('teams.projects')->get();

        $rows = collect();
        $total = $users->count();
        $submitted = 0;

        $rows->push([
            'nickname' => 'Участник',
            'status' => 'Статус',
        ]);

        foreach ($users as $user) {
            $hasProject = $user
                ->teams()
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
            'status' => "$submitted / $total ($percent%)",
        ]);

        return Excel::download(new HackathonUsersExport($rows), "hackathon_users_{$hackathon->slug}.xlsx");
    }

    public function gallery(Request $request, Hackathon $hackathon): JsonResponse
    {
        $paginator = $hackathon
            ->allProjects()
            ->with(['team.teamUsers.user', 'team.teamUsers.position', 'evaluations.criterion'])
            ->filter($request)
            ->published()
            ->paginate(12);

        return response()->json([
            'gallery' => ProjectResource::collection($paginator)->response()->getData(true),
        ]);
    }

    public function finishHackathon(Hackathon $hackathon): RedirectResponse
    {
        Gate::authorize('finish', $hackathon);

        $action = new FinishOneHackathon();
        $ok = $action($hackathon->slug);

        if (!$ok) {
            return back()->with('error', __('cannot_finish_hackathon'));
        }

        return back()->with('status', __('hackathon_finished'));
    }

    public function inviteCapitan(Request $request, Hackathon $hackathon): \Illuminate\Http\Response
    {
        $this->abortIfTeamReadOnly();

        Gate::authorize('update', $hackathon);

        $data = $request->validate([
            'users' => 'array',
            'users.*.user_id' => 'required',
        ]);

        $errors = [];

        foreach ($data['users'] as $index => $user) {
            do {
                $token = Str::random(32);
            } while (HackathonInvite::where('token', $token)->exists());

            $invitedUserId = $user['user_id'];

            if (is_string($invitedUserId)) {
                if (str_contains($invitedUserId, "ID")) {
                    $invitedUserId = (int) str_replace("ID", "", $invitedUserId);
                }

                if ($invitedUserId === 0 || is_string($invitedUserId)) {
                    $errors["users.$index.user_id"] = [__('user_not_found_by_id', ['user_id' => $user['user_id']])];
                    continue;
                }
            }

            $invitedUser = User::find($invitedUserId);
            if (!$invitedUser) {
                $errors["users.$index.user_id"] = [__('user_not_found_by_id', ['user_id' => $user['user_id']])];
                continue;
            }

            if ($hackathon->members()->where('user_id', $invitedUserId)->exists()) {
                $errors["users.$index.user_id"] = [__('user_already_hackathon_participant', ['user_nickname' => $invitedUser->nickname])];
                continue;
            }

            if ($hackathon->getAllHackathonStaff()->contains($invitedUser->id)) {
                $errors["users.$index.user_id"] = [__('user_is_staff', ['user_nickname' => $invitedUser->nickname])];
                continue;
            }

            if (HackathonInvite::where('hackathon_id', $hackathon->id)->where('user_id', $invitedUserId)->exists()) {
                $errors["users.$index.user_id"] = [__('invitation_already_sent', ['user_nickname' => $invitedUser->nickname])];
                continue;
            }

            $role = Role::find(Role::MEMBER);

            $invite = HackathonInvite::create([
                'hackathon_id' => $hackathon->id,
                'user_id' => $invitedUserId,
                'role_id' => $role->id,
                'token' => $token,
                'expires_at' => now()->addDay(),
            ]);

            $sender = auth()->user();

            $invitedUser->notify(new InviteNotification([
                'title' => __('invitation_title'),
                'description' => __('invitation_description', ['hackathon_title' => $hackathon->title, 'role_title' => $role->title]),
                'url' => route('hackathons.accept-invite-capitan', [$hackathon, $invite->token]),
                'send_at' => now()->toDateString(),
                'is_active' => true,
                'hackathon' => $hackathon,
            ]));
        }


        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return response()->noContent();
    }

    public function acceptInviteCapitan(Request $request, Hackathon $hackathon, $token): RedirectResponse
    {
        $this->abortIfTeamReadOnly();

        Gate::authorize('acceptInvite', $hackathon);

        $invite = HackathonInvite::where('token', $token)->firstOrFail();

        if ($invite->isExpired()) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', __('invitation_expired'));
        }

        $user = auth()->user();

        if ($invite->hackathon->getAllHackathonStaff()->contains($user->id)) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', __('already_staff'));
        }

        if ($invite
            ->hackathon
            ->users()
            ->with('roles')
            ->withPivot('role_id')
            ->wherePivotIn('role_id', [Role::MEMBER])
            ->get()
            ->contains($user->id)
        ) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', __('already_participant'));
        }

        $invite->hackathon->users()->attach($user->id, ['role_id' => $invite->role_id]);

        $team = $hackathon->teams()->create([
            'title' => __('team_title') . " " . $user->nickname
        ]);

        $user->teams()->syncWithoutDetaching([
            $team->id => ['position_id' => Position::CAPITAN_POSITION]
        ]);

        $user
            ->notifications()
            ->where('data->url', route('hackathons.accept-invite-capitan', [$hackathon, $invite->token]))
            ->update(['data->is_active' => false]);

        $user->assignedRole($invite->role_id);

        $invite->delete();

        return redirect()->route('hackathons.show', $hackathon)->with('status', __('joined_hackathon_success'));
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function uploadTemplate(Request $request, Hackathon $hackathon): RedirectResponse
    {
        Gate::authorize('updatePublished', $hackathon);

        $request->validate([
            'template' => ['required', 'file', 'mimes:html', 'max:2048'],
            'width' => ['nullable', 'required_with:height', 'numeric', 'min:50', 'max:1000'],
            'height' => ['nullable', 'required_with:width', 'numeric', 'min:50', 'max:1000'],
        ]);

        $content = file_get_contents($request->file('template')?->getRealPath());

        $cleanHtml = $this->safeHtmlClean($content);

        if ($hackathon->hasMedia('template')) {
            $hackathon->clearMediaCollection('template');
        }

        $media = $hackathon->addMediaFromString($cleanHtml)
            ->usingName('certificate_template')
            ->usingFileName('certificate_template.html')
            ->toMediaCollection('template');

        if ($request->filled(['width', 'height'])) {
            $media->setCustomProperty('width_mm', $request->width);
            $media->setCustomProperty('height_mm', $request->height);
            $media->save();
        }

        return back()->with('status', __('certificate_template_uploaded'));
    }

    public function uploadSeal(Request $request, Hackathon $hackathon): RedirectResponse
    {
        Gate::authorize('update', $hackathon); // или update, как вам нужно

        $request->validate([
            'seal' => ['required', 'file', 'mimes:png', 'max:2048'], // 2MB
        ]);

        if ($hackathon->hasMedia('certificate_seal')) {
            $hackathon->clearMediaCollection('certificate_seal');
        }

        $hackathon->addMediaFromRequest('seal')
            ->usingName('certificate_seal')
            ->usingFileName('certificate_seal.png')
            ->toMediaCollection('certificate_seal');

        return back()->with('success', 'Печать загружена');
    }

    private function getProfileTeamJoinAvailability(Team $team, Hackathon $hackathon): array
    {
        $errors = [];
        $membersCount = $team->teamUsers->count();
        $minTeamSize = (int) ($hackathon->min_team_size ?? 1);
        $maxTeamSize = (int) ($hackathon->max_team_size ?? 1);

        if (! $team->isProfileTeam()) {
            $errors[] = 'Можно выбрать только постоянную команду из профиля.';
        }

        if ($hackathon->type !== 'team') {
            $errors[] = 'Этот хакатон не поддерживает вступление готовой командой.';
        }

        if ($hackathon->isModeration()) {
            $errors[] = 'Для хакатонов с модерацией вступление готовой командой пока недоступно.';
        }

        if ($membersCount < $minTeamSize) {
            $errors[] = "В команде недостаточно участников: минимум {$minTeamSize}.";
        }

        if ($membersCount > $maxTeamSize) {
            $errors[] = "В команде слишком много участников: максимум {$maxTeamSize}.";
        }

        foreach ($team->teamUsers as $teamUser) {
            $member = $teamUser->user;
            if (! $member) {
                continue;
            }

            if ($member->status === User::STATUS_BLOCKED) {
                $errors[] = "Участник @{$member->nickname} заблокирован.";
            }

            if ($member->isHackathonStaff($hackathon)) {
                $errors[] = "Участник @{$member->nickname} уже относится к стаффу этого хакатона.";
            }

            if ($member->isAdmin()) {
                $errors[] = "Участник @{$member->nickname} является администратором и не может быть добавлен в команду хакатона.";
            }

            if ($member->hackathons()->where('hackathon_id', $hackathon->id)->exists()) {
                $errors[] = "Участник @{$member->nickname} уже участвует в этом хакатоне.";
            }
        }

        return [
            'members_count' => $membersCount,
            'can_join_hackathon' => empty($errors),
            'join_errors' => array_values(array_unique($errors)),
        ];
    }

    private function registerProfileTeamOnHackathon(Hackathon $hackathon, Team $profileTeam): void
    {
        DB::transaction(function () use ($hackathon, $profileTeam) {
            $hackathonTeam = $hackathon->teams()->create([
                'title' => $profileTeam->title,
            ]);

            $hackathonUsers = $profileTeam->teamUsers->mapWithKeys(function ($teamUser) {
                return [
                    $teamUser->user_id => ['role_id' => Role::MEMBER],
                ];
            })->all();

            $hackathon->users()->syncWithoutDetaching($hackathonUsers);

            $teamMembers = $profileTeam->teamUsers->mapWithKeys(function ($teamUser) {
                return [
                    $teamUser->user_id => ['position_id' => $teamUser->position_id],
                ];
            })->all();

            $hackathonTeam->users()->syncWithoutDetaching($teamMembers);
        });
    }

    private function abortIfTeamReadOnly(): void
    {
        if (Team::isReadOnlyMode()) {
            abort(403, 'Управление командами доступно только на Foncode.');
        }
    }

    private function membershipResponse(
        Request $request,
        string $flashKey,
        string $message,
        array $payload = [],
        int $status = 200
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(array_merge([
                $flashKey => $message,
            ], $payload), $status);
        }

        return back()->with($flashKey, $message);
    }

    private function mediaToDataUrl(?Media $media): ?string
    {
        if (!$media) return null;

        $path = $media->getPath();
        if (!is_file($path)) return null;

        $mime = $media->mime_type ?: 'image/png';
        $data = base64_encode(file_get_contents($path));

        return "data:$mime;base64,$data";
    }

    public function downloadPreviewCertificate(Hackathon $hackathon): \Symfony\Component\HttpFoundation\Response
    {
        Gate::authorize('update', $hackathon);

        $templateMedia = $hackathon->getFirstMedia('template');

        // Печать как в дефолтном (data-url)
        $sealMedia = $hackathon->getFirstMedia('certificate_seal');
        $sealSrc = $this->mediaToDataUrl($sealMedia);

        // fallback: абсолютный URL
        if (!$sealSrc) {
            $sealUrl = $hackathon->getFirstMediaUrl('certificate_seal'); // может быть "/storage/.."
            $sealSrc = $sealUrl ? asset($sealUrl) : '';
        }

        $defaultPaperPt = [0, 0, 1032, 732];

        if ($templateMedia) {
            $template = file_get_contents($templateMedia->getPath());

            $m = new Engine();
            $html = $m->render($template, [
                'hackathonTitle' => $hackathon->title,
                'userName' => 'Test User',
                'userNickname' => 'testuser',
                'place' => 1,
                'organizatorNickname' => $hackathon->owner->nickname,
                'startTime' => $hackathon->event_start->format('d.m.Y'),
                'endTime' => $hackathon->event_end->format('d.m.Y'),
                'seal' => $sealSrc,
            ]);

            if ($sealSrc) {
                $hasAnyImg = str_contains($html, '<img');
                $sealAsTextExists = str_contains($html, $sealSrc);

                if (!$hasAnyImg && $sealAsTextExists) {
                    $html = str_replace($sealSrc, '<img src="'.$sealSrc.'" alt="seal">', $html);
                } elseif ($sealAsTextExists && !str_contains($html, 'src="'.$sealSrc.'"') && !str_contains($html, "src='".$sealSrc."'")) {
                    $pos = strpos($html, $sealSrc);
                    if ($pos !== false) {
                        $html = substr_replace($html, '<img src="'.$sealSrc.'" alt="seal">', $pos, strlen($sealSrc));
                    }
                }
            }

            $widthMm  = (float) $templateMedia->getCustomProperty('width_mm');
            $heightMm = (float) $templateMedia->getCustomProperty('height_mm');

            if ($widthMm <= 0)  $widthMm = 297;
            if ($heightMm <= 0) $heightMm = 210;

            // авто-фикс: если случайно сохранили pt вместо mm (842/595, 1032/732 и т.п.)
            if ($widthMm > 500 || $heightMm > 500) {
                $widthMm  = $widthMm / 2.83464567;   // pt -> mm
                $heightMm = $heightMm / 2.83464567;
            }

            // mm -> pt (то, что ждёт setPaper)
            $widthPt  = $widthMm  * 72 / 25.4;
            $heightPt = $heightMm * 72 / 25.4;

            $paper = [0, 0, $widthPt, $heightPt];


            $pdf = Pdf::loadHTML($html)
                ->setOption(['defaultFont' => 'Helvetica'])
                ->setOption('margin-top', 0)
                ->setOption('margin-bottom', 0)
                ->setOption('margin-left', 0)
                ->setOption('margin-right', 0)
                ->setOption('dpi', 300)
                ->setOption('zoom', 1.0)
                ->setOption('enable-local-file-access', true)
                ->setPaper($paper);

            return $pdf->download('preview-certificate.pdf');
        }

        // дефолтный сертификат
        $pdf = Pdf::loadView('certificate', [
            'hackathonTitle' => $hackathon->title,
            'userName' => 'Test User',
            'userNickname' => 'testuser',
            'place' => 1,
            'organizatorNickname' => $hackathon->owner->nickname,
            'startTime' => $hackathon->event_start->format('d.m.Y'),
            'endTime' => $hackathon->event_end->format('d.m.Y'),
            'seal' => $sealSrc,
        ])
            ->setOption(['defaultFont' => 'Helvetica'])
            ->setPaper($defaultPaperPt);

        return $pdf->download('preview-certificate.pdf');
    }


    private function safeHtmlClean($content)
    {
        $patterns = [
            '/<script\b[^>]*>.*?<\/script>/is',
            '/<iframe\b[^>]*>.*?<\/iframe>/is',
            '/<object\b[^>]*>.*?<\/object>/is',
            '/<embed\b[^>]*>.*?<\/embed>/is',
            '/<applet\b[^>]*>.*?<\/applet>/is',
            '/<form\b[^>]*>.*?<\/form>/is',
            '/<input\b[^>]*>/is',
            '/<textarea\b[^>]*>.*?<\/textarea>/is',
            '/<select\b[^>]*>.*?<\/select>/is',
            '/<button\b[^>]*>.*?<\/button>/is',

            '/\son(load|error|click|mouse|key)\s*=\s*["\'][^"\']*["\']/i',
            '/\son\w+\s*=\s*["\'][^"\']*["\']/i',

            '/javascript:\s*[^"\']*/i',
            '/vbscript:\s*[^"\']*/i',
            '/data:\s*text\/html/i',
            '/data:\s*application\/x-javascript/i',

            '/<meta[^>]*http-equiv\s*=\s*["\']refresh["\'][^>]*>/i',
        ];

        $cleaned = preg_replace($patterns, '', $content);

        return $this->validateHtmlStructure($cleaned);
    }

    private function validateHtmlStructure($html)
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $scripts = $dom->getElementsByTagName('script');
        foreach (iterator_to_array($scripts) as $script) {
            $script->parentNode->removeChild($script);
        }

        $iframes = $dom->getElementsByTagName('iframe');
        foreach (iterator_to_array($iframes) as $iframe) {
            $iframe->parentNode->removeChild($iframe);
        }

        $forms = $dom->getElementsByTagName('form');
        foreach (iterator_to_array($forms) as $form) {
            $form->parentNode->removeChild($form);
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[@onload or @onerror or @onclick or @onmouseover or @onkeypress]');
        foreach ($nodes as $node) {
            $node->removeAttribute('onload');
            $node->removeAttribute('onerror');
            $node->removeAttribute('onclick');
            $node->removeAttribute('onmouseover');
            $node->removeAttribute('onkeypress');
        }

        return $dom->saveHTML();
    }
}
