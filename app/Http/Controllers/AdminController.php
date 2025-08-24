<?php

namespace App\Http\Controllers;

use App\Http\Resources\HackathonResource;
use App\Http\Resources\ProjectResource;
use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Project;
use App\Notifications\ModerateNotification;
use Carbon\Carbon;
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

        return Inertia::render('Admin/Moderation/Hackathon', [
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
}
