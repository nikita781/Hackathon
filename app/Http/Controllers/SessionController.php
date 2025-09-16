<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function loginView(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('nickname', $credentials['login'])
            ->orWhere('email', $credentials['login'])
            ->first();

        if (!$user) {
            $user = DB::connection('main_site')
                ->table('users')
                ->where('name', $credentials['login'])
                ->orWhere('email', $credentials['login'])
                ->first();
        }

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'login' => 'Неверный логин или пароль',
            ]);
        }

        if (!$user->hasRole(Role::MEMBER)) {
            $user->assignedRole(Role::MEMBER);
        }

        Auth::login($user);

        return redirect()->route('home');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        return redirect()->route('home');
    }
}
