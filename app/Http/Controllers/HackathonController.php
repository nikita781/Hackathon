<?php

namespace App\Http\Controllers;

use App\Models\Hackathon;
use Illuminate\Http\Request;
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
        return Inertia::render('Hackathon', [
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

    public function show(Hackathon $hackathon)
    {

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
