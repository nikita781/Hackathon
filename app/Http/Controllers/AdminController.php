<?php

namespace App\Http\Controllers;

use App\Actions\FinishHackathons;
use App\Actions\SyncUsers;
use App\Http\Resources\AwardResource;
use App\Http\Resources\HackathonResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SupportResource;
use App\Http\Resources\TagResource;
use App\Models\Award;
use App\Models\Banner;
use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Support;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\ModerateNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class AdminController extends Controller
{
    public function moderationHackathons(Request $request): Response
    {
        $perPage = min($request->get('per_page', 12), 12);

        $hackathons = Hackathon::adminFilter($request)
            ->where('status', '!=', Hackathon::STATUS_DRAFT)
            ->with(['owner', 'tags'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Moderation/Hackathon', [
            'hackathons' => HackathonResource::collection($hackathons),
            'filters' => $request->only(
                'q',
                'status',
                'order'
            ),
        ]);
    }

    public function moderationProjectsHackathons(Request $request): Response
    {
        $perPage = min($request->get('per_page', 12), 12);

        $hackathons = Hackathon::adminFilter($request)
            ->where('status', '!=', Hackathon::STATUS_DRAFT)
            ->with(['owner', 'tags'])
            ->withProjectCounts()
            ->withUserCounts()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Moderation/Projects/Hackathons', [
            'hackathons' => HackathonResource::collection($hackathons),
            'filters' => $request->only(
                'q',
                'status',
                'order'
            ),
        ]);
    }

    public function moderationProjects(Request $request, Hackathon $hackathon): Response
    {
        $perPage = min($request->get('per_page', 9), 9);

        $projects = $hackathon
            ->allProjects()
            ->where('status', '!=', Project::DRAFT)
            ->with(['team.teamUsers.user', 'team.teamUsers.position'])
            ->adminFilter($request)
            ->orderBy('moderated_time')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Moderation/Projects/List', [
            'projects' => ProjectResource::collection($projects),
            'hackathon' => new HackathonResource($hackathon),
            'filters' => $request->only('q', 'status', 'order'),
        ]);
    }

    public function acceptHackathon(Request $request, Hackathon $hackathon): RedirectResponse
    {
        Gate::authorize('moderate', Hackathon::class);

        switch ($hackathon->status) {
            case Hackathon::STATUS_PUBLISHED:
                return back()->with('error', 'Хакатон уже опубликован');
            case Hackathon::STATUS_BLOCKED:
                return back()->with('error', 'Хакатон уже отклонен');
        }

        $comment = $request->validate([
            'comment' => ['nullable', 'string', 'max:255', 'min:3']
        ]);

        $hackathon->update([
            'status' => Hackathon::STATUS_PUBLISHED,
            'published_time' => Carbon::now(),
            'comment' => $comment
        ]);

        $message = 'Хакатон "' . $hackathon->title . '" опубликован';

        $hackathon->owner->notify(new ModerateNotification([
            'status' => 'accept',
            'comment' => $comment,
            'title' => $message,
            'send_at' => now()->toDateString(),
            'hackathon' => $hackathon,
            'project' => null,
        ]));

        return back()->with('status', $message);
    }

    public function rejectHackathon(Request $request, Hackathon $hackathon): RedirectResponse
    {
        Gate::authorize('moderate', Hackathon::class);

        switch ($hackathon->status) {
            case Hackathon::STATUS_PUBLISHED:
                return back()->with('error', 'Хакатон уже опубликован');
            case Hackathon::STATUS_BLOCKED:
                return back()->with('error', 'Хакатон уже отклонен');
        }

        $comment = $request->validate([
            'comment' => ['nullable', 'string', 'max:255', 'min:3']
        ]);

        $hackathon->update([
            'status' => Hackathon::STATUS_BLOCKED,
            'published_time' => Carbon::now(),
            'comment' => $comment
        ]);

        $message = 'Хакатон "' . $hackathon->title . '" отклонен';

        $hackathon->owner->notify(new ModerateNotification([
            'status' => 'rejected',
            'comment' => $comment,
            'title' => $message,
            'send_at' => now()->toDateString(),
            'hackathon' => $hackathon,
            'project' => null,
        ]));

        return back()->with('status', $message);
    }

    public function acceptProject(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('moderate', Project::class);

        switch ($project->status) {
            case Project::PUBLISHED:
                return back()->with('error', 'Проект уже опубликован');
            case Project::BLOCKED:
                return back()->with('error', 'Проект уже отклонен');
        }

        $comment = $request->validate([
            'comment' => ['nullable', 'string', 'max:255', 'min:3']
        ]);

        $project->update([
            'status' => Project::PUBLISHED,
            'published_time' => Carbon::now(),
            'comment' => $comment
        ]);

        $project->load('hackathon');

        $message = 'Проект "' . $project->title . '" опубликован';

        if ($captain = $project->team->captain()) {
            $captain->notify(new ModerateNotification([
                'status' => 'accept',
                'comment' => $comment,
                'title' => $message,
                'send_at' => now()->toDateString(),
                'hackathon' => null,
                'project' => new ProjectResource($project),
            ]));
        }

        return back()->with('status', $message);
    }

    public function rejectProject(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('moderate', Project::class);

        switch ($project->status) {
            case Project::PUBLISHED:
                return back()->with('error', 'Проект уже опубликован');
            case Project::BLOCKED:
                return back()->with('error', 'Проект уже отклонен');
        }

        $comment = $request->validate([
            'comment' => ['nullable', 'string', 'max:255', 'min:3']
        ]);

        $project->update([
            'status' => Project::BLOCKED,
            'published_time' => Carbon::now(),
            'comment' => $comment
        ]);

        $project->load('hackathon');

        $message = 'Проект "' . $project->title . '" отклонен';

        if ($captain = $project->team->captain()) {
            $captain->notify(new ModerateNotification([
                'status' => 'rejected',
                'comment' => $comment,
                'title' => $message,
                'send_at' => now()->toDateString(),
                'hackathon' => null,
                'project' => new ProjectResource($project),
            ]));
        }

        return back()->with('status', $message);
    }

    public function support(Request $request): Response
    {
        $support = Support::query()
            ->where('type', Support::BUG)
            ->with('messages.user')
            ->with('hackathon')
            ->with('creator')
            ->filter($request)
            ->latest()
            ->paginate(12);

        return Inertia::render('Admin/Support', [
            'support' => SupportResource::collection($support),
        ]);
    }

    public function users(Request $request): Response
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('id')
            ->filter($request)
            ->paginate(12);

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    public function blockUser(User $user): RedirectResponse
    {
        Gate::authorize('blockUser', User::class);

        $user->update([
            'status' => User::STATUS_BLOCKED
        ]);

        return back()->with('status', 'Пользователь заблокирован');
    }

    public function unblockUser(User $user): RedirectResponse
    {
        Gate::authorize('blockUser', User::class);

        $user->update([
            'status' => User::STATUS_ACTIVE
        ]);

        return back()->with('status', 'Пользователь разблокирован');
    }

    public function changeRoles(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('changeRoles', User::class);

        $data = $request->validate([
            'roles' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (empty($value)) {
                        $fail('У пользователя должна быть хотя бы одна роль.');
                    }
                },
            ],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user->roles()->sync($data['roles']);

        return back()->with('status', 'Роли пользователя изменены');
    }

    public function allRoles(): JsonResponse
    {
        $roles = Role::where('id', '!=', Role::SUPER_ADMIN)->orderBy('id')->get();

        return response()->json([
            'roles' => $roles,
        ]);
    }

    public function staffRoles(): JsonResponse
    {
        $roles = Role::whereIn('id', Role::STAFF)->get();

        return response()->json([
            'roles' => $roles,
        ]);
    }

    public function tags(): Response
    {
        Gate::authorize('moderate', Hackathon::class);

        $tags = Tag::query()
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/Tag', [
            'tags' => TagResource::collection($tags),
        ]);
    }

    public function storeTag(Request $request): RedirectResponse
    {
        Gate::authorize('moderate', Hackathon::class);

        $countTag = Tag::query()->count();

        if ($countTag >= 25) {
            return back()->with('error', 'Максимальное количество тегов - 25');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:50'],
        ]);

        $data['slug'] = Tag::generateUniqueSlug($data['title']);

        $lastTagOrder = Tag::max('order') ?? 0;
        $data['order'] = $lastTagOrder + 1;

        Tag::create($data);

        return back()->with('status', 'Тег успешно добавлен');
    }

    public function updateTag(Request $request, Tag $tag): RedirectResponse
    {
        Gate::authorize('moderate', Hackathon::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:50'],
        ]);

        $data['slug'] = Tag::generateUniqueSlug($data['title']);

        $tag->update($data);

        return back()->with('status', 'Тег успешно обновлен');
    }

    public function deleteTag(Tag $tag): RedirectResponse
    {
        Gate::authorize('moderate', Hackathon::class);

        $tag->delete();

        return back()->with('status', 'Тег успешно удален');
    }

    public function changeTagOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tags' => ['required', 'array'],
            'tags.*.slug' => ['required', 'string', 'exists:tags,slug'],
            'tags.*.order' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['tags'] as $tag) {
                DB::table('tags')
                    ->where('slug', $tag['slug'])
                    ->update(['order' => $tag['order']]);
            }
        });

        return back()->with('status', 'Порядок тегов успешно изменен');
    }

    public function banners(): Response
    {
        $banners = Banner::query()
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/Banner', [
            'banners' => $banners,
        ]);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function storeBanner(Request $request): RedirectResponse
    {
        $count = Banner::count();

        if ($count >= 10) {
            return back()->with('error', 'Максимальное количество баннеров — 10');
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        $lastOrder = Banner::max('order') ?? 0;

        $banner = Banner::create([
            'order' => $lastOrder + 1,
        ]);

        $banner->addMediaFromRequest('image')->toMediaCollection('image');

        return back()->with('status', 'Баннер успешно добавлен');
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function updateBanner(Request $request, Banner $banner): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        if ($request->hasFile('image')) {
            $banner->clearMediaCollection('image');
            $banner->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return back()->with('status', 'Баннер успешно обновлен');
    }

    public function deleteBanner(Banner $banner): RedirectResponse
    {
        $banner->clearMediaCollection('image');

        $banner->delete();

        return back()->with('status', 'Баннер успешно удалён');
    }

    public function changeBannerOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'banners' => ['required', 'array'],
            'banners.*.id' => ['required', 'integer', 'exists:banners,id'],
            'banners.*.order' => ['required', 'integer'],
        ]);

        Banner::upsert($data['banners'], ['id'], ['order']);

        DB::transaction(function () use ($data) {
            foreach ($data['banners'] as $banner) {
                DB::table('banners')
                    ->where('id', $banner['id'])
                    ->update(['order' => $banner['order']]);
            }
        });

        return back()->with('status', 'Порядок баннеров успешно изменён');
    }

    public function awards(): Response
    {
        $awards = Award::query()
            ->where('system', true)
            ->get();

        return Inertia::render('Admin/Award', [
            'awards' => AwardResource::collection($awards),
        ]);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function updateAward(Request $request, Award $award): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            $award->clearMediaCollection('image');
            $award->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return back()->with('status', 'Изображение награды успешно обновлено');
    }

    public function syncUser(): JsonResponse
    {
        $action = new SyncUsers;
        $counters = $action();

        return response()->json([
            'created' => $counters['created'],
            'updated' => $counters['created'],
        ]);
    }

    public function finishHackathons(): JsonResponse
    {
        $action = new FinishHackathons;
        $hackathonTitles = $action();

        return response()->json([
            'titles' => $hackathonTitles,
        ]);
    }
}
