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
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Support;
use App\Models\Tab;
use App\Models\Tag;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
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
            'message' => "Хакатон '".$hackathon->title."' успешно создан",
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
            'criteriaGroups.criteria.evaluations',
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

        if ($request->wantsJson()) {
            return response()->json([
                'hackathon' => $hackathonResource->response(),
                'tabs' => $tabsResource->response(),
                'ownTeam' => optional($ownTeamResource)->response(),
                'allProjects' => optional($allProjects)->response(),
                'positions' => $positionsResource->response(),
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
        return back()->with('status', 'Хакатон обновлен');
    }

    public function publish(Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('publish', $hackathon)) {
            return back()->with('error', 'Вы не можете опубликовать хакатон');
        }

        if ($hackathon->work_time_start === null || $hackathon->work_time_end === null || $hackathon->evaluation_start === null || $hackathon->evaluation_end === null) {
            return back()->with('error', 'Все даты хакатона должны быть заполнены');
        }

        if ($hackathon->registration_end < now()) {
            return back()->with('error', 'Дата конца регистрации уже прошла');
        }

        if (!$hackathon->criteriaGroups()->has('criteria')->exists()) {
            return back()->with('error', 'Хакатон должен содержать критерии оценки');
        }

        if (!$hackathon->users()->wherePivot('role_id', Role::JUDGE)->exists()) {
            return back()->with('error', 'Перед публикацией пригласите хотя бы одного судью');
        }

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
            abort(403);
        }

        $user = auth()->user();

        $user->hackathons()->attach($hackathon->id, ['role_id' => Role::MEMBER]);

        $team = $hackathon->teams()->create([
            'title' => "Команда ".$user->nickname
        ]);

        $user->teams()->syncWithoutDetaching([
            $team->id => ['position_id' => Position::CAPITAN_POSITION]
        ]);

        return back()->with('status', 'Вы успешно присоединились к хакатону!');
    }

    public function leaveHackathon(Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('leave', $hackathon)) {
            abort(403);
        }

        $user = auth()->user();
        $team = $user->teams()->where('hackathon_id', $hackathon->id)->first();

        if (!$team) {
            return back()->with('error', 'Вы не состоите в команде этого хакатона.');
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

            return back()->with('status', 'Вы покинули хакатон, команда и проект были удалены.');
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

        return back()->with('status', 'Вы покинули хакатон.');
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
            return back()->with('error', "Сейчас хакатон нельзя завершить");
        }

        return back()->with('status', "Хакатон \"{$hackathon->slug}\" завершен");
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

        return back()->with('success', 'Шаблон сертификата успешно загружен');
    }

    public function downloadPreviewCertificate(Hackathon $hackathon): \Illuminate\Http\Response
    {
        Gate::authorize('update', $hackathon);

        $templateMedia = $hackathon->getMedia('template')->first();
        if ($templateMedia) {
            $template = file_get_contents($templateMedia->getPath());

            $m = new Engine();
            $html = $m->render($template, [
                'hackathonTitle' => $hackathon->title,
                'userName' => 'Тестовый пользователь',
                'userNickname' => 'testuser',
                'place' => 1,
                'organizatorNickname' => $hackathon->owner->nickname,
                'startTime' => $hackathon->event_start->format('d.m.Y'),
                'endTime' => $hackathon->event_end->format('d.m.Y'),
                'seal' => null,
            ]);

            $width = ($templateMedia->getCustomProperty('width_mm') ?? 297) * 2.8346;
            $height = ($templateMedia->getCustomProperty('height_mm') ?? 210) * 2.8346;

            $customPaper = [0, 0, $width, $height];

            if ($width === 0.0 || $height === 0.0) {
                $customPaper = "A4";
            }

            $pdf = Pdf::loadHTML($html)
                ->setOption(['defaultFont' => 'Helvetica'])
                ->setOption('margin-top', 0)
                ->setOption('margin-bottom', 0)
                ->setOption('margin-left', 0)
                ->setOption('margin-right', 0)
                ->setPaper('a4', 'landscape')
                ->setOption('dpi', 300)
                ->setOption('zoom', 1.0)
                ->setPaper($customPaper);

            return $pdf->stream("preview-certificate.pdf");
        }

        $customPaper = [0, 0, 1032, 732];

        $pdf = Pdf::loadView('certificate', [
            'hackathonTitle' => $hackathon->title,
            'userName' => 'Тестовый пользователь',
            'userNickname' => 'testuser',
            'place' => 1,
            'organizatorNickname' => $hackathon->owner->nickname,
            'startTime' => $hackathon->event_start->format('d.m.Y'),
            'endTime' => $hackathon->event_end->format('d.m.Y'),
            'seal' => null,
        ])
            ->setOption(['defaultFont' => 'Helvetica'])
            ->setPaper($customPaper);

        return $pdf->download("preview-certificate.pdf");
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
