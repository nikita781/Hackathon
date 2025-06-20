<?php

namespace App\Http\Controllers;

use App\Http\Requests\HackathonRequest;
use App\Http\Resources\HackathonResource;
use App\Http\Resources\TabResource;
use App\Http\Resources\TagResource;
use App\Models\Hackathon;
use App\Models\Role;
use App\Models\Tab;
use App\Models\Tag;
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
    public function store(HackathonRequest $request): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['tags', 'image_path']);
        $data['slug'] = Hackathon::generateUniqueSlug($data['title']);
        $user = auth()->user();
        $hackathon = $user->hackathonsAsOrganizer()->create($data);
        if ($request->hasFile('image_path')) {
            $hackathon->addMediaFromRequest('image_path')->toMediaCollection('main_image');
        }
        $hackathon->tags()->sync($request->tags);

        foreach (Tab::defaultStructure() as $tabTitle => $sections) {
            $tab = $hackathon->tabs()->create(['title' => $tabTitle]);

            foreach ($sections as $sectionTitle) {
                $tab->sections()->create(['title' => $sectionTitle]);
            }
        }

        return redirect()->route('hackathons.show', $hackathon);
    }

    /**
     * @param  Hackathon  $hackathon
     * @return Response
     */
    public function show(Hackathon $hackathon): Response
    {
        if (!Gate::check('view', [$hackathon])) {
            abort(404);
        }
        $hackathon->load([
            'tags',
        ]);

        $tabs = $hackathon->tabs()->with(['sections.items', 'media'])->get();

//        $hackathon->projects->each(function ($project) {
//            $project->members->each(function ($member) {
//                $member->pivot->load('position');
//            });
//        });

        return Inertia::render('Hackathon/Show', [
            'hackathon' => new HackathonResource($hackathon),
            'tabs' => TabResource::collection($tabs)->additional([
                'hackathon' => $hackathon->id,
            ]),
            'can' => [
                'update' => Gate::check('update', $hackathon),
                'delete' => Gate::check('delete', $hackathon),
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
