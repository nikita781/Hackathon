<?php

namespace App\Http\Controllers;

use App\Models\Hackathon;
use App\Models\Role;
use Illuminate\Http\Request;
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
        return Inertia::render('Hackathon/Index', [
//            'hackathons' => Hackathon::filter($request)->paginate($request->per_page ?? 6),
            'hackathons' => Hackathon::filter($request)->with('tags')->get(),

        ]);
    }

    public function create()
    {
    }

    public function store(Request $request)
    {
    }

    /**
     * @param  Hackathon  $hackathon
     * @return Response
     */
    public function show(Hackathon $hackathon): Response
    {

        $hackathon->load([
            'tags',
            'projects' => function ($query) {
                $query->with([
                    'members', 'capitan', 'images'
                ]);
            }
        ]);

        $hackathon->projects->each(function ($project) {
            $project->members->each(function ($member) {
                $member->pivot->load('position');
            });
        });

        return Inertia::render('Hackathon/Show', [
            'hackathon' => $hackathon,
            'canEdit' => Gate::check('update', $hackathon),
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
