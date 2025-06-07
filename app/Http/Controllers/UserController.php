<?php

namespace App\Http\Controllers;

use App\Http\Resources\HackathonResource;
use App\Models\Hackathon;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function show(User $user)
    {
        return Inertia::render('Dashboard', [
            'user' => $user->load('roles'),
        ]);
    }
}
