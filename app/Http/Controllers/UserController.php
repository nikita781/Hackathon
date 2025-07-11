<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function show(User $user): Response
    {
        return Inertia::render('Dashboard', [
            'user' => $user->load('roles'),
        ]);
    }

    public function showMe(): Response
    {
        return $this->show(auth()->user());
    }
}
