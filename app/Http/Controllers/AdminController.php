<?php

namespace App\Http\Controllers;

use App\Http\Resources\HackathonResource;
use App\Http\Resources\ProjectResource;
use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Support;
use App\Models\User;
use App\Notifications\ModerateNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function moderationHackathon(Request $request): Response
    {
        $perPage = min($request->get('per_page', 12), 12);

        $hackathons = Hackathon::adminFilter($request)
            ->with('owner')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Moderation/Hackathon', [
            'hackathons' => HackathonResource::collection($hackathons),
            'filters'    => $request->only(
                'q', 'status', 'order'
            ),
        ]);
    }

    public function moderationProject(Request $request): Response
    {
        $perPage = min($request->get('per_page', 12), 12);

        $hackathons = Hackathon::whereHas('allProjects', function ($query) use ($request) {
                $query->adminFilter($request);
            })
            ->with(['allProjects' => function ($query) use ($request) {
                $query->adminFilter($request);
            }])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Moderation/Project', [
            'hackathons' => HackathonResource::collection($hackathons),
            'filters'    => $request->only(
                'q', 'status', 'order'
            ),
        ]);
    }

    public function acceptHackathon(Request $request, Hackathon $hackathon): RedirectResponse
    {
        Gate::authorize('moderate', Hackathon::class);

        $comment = $request->validate([
            'comment' => ['nullable', 'string', 'max:255', 'min:3']
        ]);

        $hackathon->update([
            'status' => Hackathon::STATUS_PUBLISHED,
            'published_time' => Carbon::now(),
            'comment' => $comment
        ]);

        $message = "Хакатон \"". $hackathon->title ."\" опубликован";

        $hackathon->owner->notify(new ModerateNotification([
            'status' => 'accept',
            'comment' => $comment,
            'message' => $message,
        ]));

        return back()->with('status', $message);
    }

    public function rejectHackathon(Request $request, Hackathon $hackathon): RedirectResponse
    {
        Gate::authorize('moderate', Hackathon::class);

        $comment = $request->validate([
            'comment' => ['nullable', 'string', 'max:255', 'min:3']
        ]);

        $hackathon->update([
            'status' => Hackathon::STATUS_BLOCKED,
            'published_time' => Carbon::now(),
            'comment' => $comment
        ]);

        $message = "Хакатон \"". $hackathon->title ."\" отклонен";

        $hackathon->owner->notify(new ModerateNotification([
            'status' => 'rejected',
            'comment' => $comment,
            'message' => $message,
        ]));

        return back()->with('status', $message);
    }

    public function acceptProject(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('moderate', Project::class);

        $comment = $request->validate([
            'comment' => ['nullable', 'string', 'max:255', 'min:3']
        ]);

        $project->update([
            'status' => Project::BLOCKED,
            'published_time' => Carbon::now(),
            'comment' => $comment
        ]);

        $message = "Проект \"". $project->title ."\" опубликован";

        if ($captain = $project->team->captain()->first()) {
            $captain->notify(new ModerateNotification([
                'status' => 'rejected',
                'comment' => $comment,
                'message' => $message,
            ]));
        }

        return back()->with('status', $message);
    }

    public function rejectProject(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('moderate', Project::class);

        $comment = $request->validate([
            'comment' => ['nullable', 'string', 'max:255', 'min:3']
        ]);

        $project->update([
            'status' => Project::BLOCKED,
            'published_time' => Carbon::now(),
            'comment' => $comment
        ]);

        $message = "Проект \"". $project->title ."\" отклонен";

        if ($captain = $project->team->captain()->first()) {
            $captain->notify(new ModerateNotification([
                'status' => 'rejected',
                'comment' => $comment,
                'message' => $message,
            ]));
        }

        return back()->with('status', $message);
    }

    public function support(Request $request): Response
    {
        $support = Support::query()
            ->with('messages.user')
            ->filter($request)
            ->where('is_completed', false)
            ->latest()
            ->paginate(12);

        return Inertia::render('Admin/Support', [
            'support' => $support,
        ]);
    }

    public function users(Request $request): Response
    {
        $users = User::query()
            ->with('roles')
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

        return back()->with('status', "Пользователь заблокирован");
    }

    public function unblockUser(User $user): RedirectResponse
    {
        Gate::authorize('blockUser', User::class);

        $user->update([
            'status' => User::STATUS_ACTIVE
        ]);

        return back()->with('status', "Пользователь разблокирован");
    }

    public function changeRoles(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('changeRoles', User::class);

        $data = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $newRoles = $data['roles'];

        $currentRoles = $user->roles()->pluck('id')->toArray();

        if (empty($newRoles)) {
            return back()->with('error', 'У пользователя должна быть хотя бы одна роль');
        }

        $user->roles()->sync($newRoles);

        return back()->with('status', 'Роли пользователя изменены');
    }

    public function allRoles(): JsonResponse
    {
        $roles = Role::all();

        return response()->json([
            'roles' => $roles,
        ]);
    }
}
