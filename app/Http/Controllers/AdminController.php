<?php

namespace App\Http\Controllers;

use App\Http\Resources\HackathonResource;
use App\Http\Resources\ProjectResource;
use App\Models\Hackathon;
use App\Models\Project;
use Illuminate\Http\Request;
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
}
