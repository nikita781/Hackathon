<?php

namespace App\Http\Controllers;

use App\Http\Resources\HackathonResource;
use App\Models\Hackathon;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $hackathons = auth()->user()
            ->hackathons()
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth()->user()->id)
                    ->where('role_id', Role::ORGANIZER);
            })
            ->with('tags')
            ->get();

        return Inertia::render('Dashboard', [
            'user' => auth()->user()->load('roles'),
            'hackathons' => HackathonResource::collection($hackathons),
            'can' => [
                'create' => auth()->user()->can('create', Hackathon::class),
            ],
        ]);
    }
}
