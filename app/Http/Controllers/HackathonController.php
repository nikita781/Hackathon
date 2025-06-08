<?php

namespace App\Http\Controllers;

use App\Http\Requests\HackathonRequest;
use App\Http\Resources\HackathonResource;
use App\Http\Resources\TagResource;
use App\Models\Hackathon;
use App\Models\Role;
use App\Models\Tab;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

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
            'tags' => HackathonResource::collection(Tag::orderBy('title')->get()),
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
                $user->hackathonsAsOrganizer()->select('hackathons.id')
            );
        }

        $upcoming = Hackathon::query()
            ->whereIn('id', $hackathonIds)
            ->filter($request)
            ->where('event_start', '>', now())
            ->with('tags')
            ->orderBy('event_start')
            ->paginate($perPage);

        $past = Hackathon::query()
            ->whereIn('id', $hackathonIds)
            ->filter($request)
            ->where('event_start', '<=', now())
            ->with('tags')
            ->orderByDesc('event_start')
            ->paginate($perPage);

        return Inertia::render('Dashboard', [
            'user' => $user->load('roles'),
            'upcomingHackathons' => $upcoming,
            'pastHackathons' => $past,
            'can' => [
                'create' => $user->can('create', Hackathon::class),
            ],
        ]);
    }

    public function create()
    {
    }

    public function store(HackathonRequest $request)
    {
        $data = Arr::except($request->validated(), 'tags');
        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')?->store('hackathons', 'public');
        }
        $data['slug'] = Hackathon::generateUniqueSlug($data['title']);
        $hackathon = Hackathon::create($data);
        $hackathon->users()->syncWithoutDetaching([
            auth()->id() => ['role_id' => Role::ORGANIZER],
        ]);
        $hackathon->tags()->sync($request->tags);
        foreach (Tab::TAB_TITLES as $tab) {
            $hackathon->tabs()->create([
                'title' => $tab
            ]);
        }

        return redirect()->route('hackathons.show', $hackathon);
    }

    /**
     * @param  Hackathon  $hackathon
     * @return Response
     */
    public function show(Hackathon $hackathon): Response
    {
        if (!Gate::check('view', $hackathon)) {
            abort(404);
        }
        $hackathon->load([
            'tags',
            'projects' => function ($query) {
                $query->with([
                    'members', 'capitan', 'images'
                ]);
            },
            'tabs' => function ($query) {
                $query->with('images');
            }
        ]);

        $hackathon->projects->each(function ($project) {
            $project->members->each(function ($member) {
                $member->pivot->load('position');
            });
        });

        return Inertia::render('Hackathon/Show', [
            'hackathon' => new HackathonResource($hackathon),
            'can' => [
                'update' => Gate::check('update', $hackathon),
            ],
        ]);
    }

    public function edit(Hackathon $hackathon)
    {
    }

    public function update(Request $request, Hackathon $hackathon)
    {

    }

    public function destroy(Hackathon $hackathon)
    {
    }
}
