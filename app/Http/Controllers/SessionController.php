<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(): Response
    {
        abort_unless(app()->environment('local'), 403, 'Только в локальном режиме');
        return Inertia::render('Auth/FakeOAuthLogin', [
            'users' => User::query()->with('roles')->select(['id', 'name', 'email'])->get(),
        ]);
    }

    public function login(): RedirectResponse
    {
        return redirect()->route('auth.redirect');
    }

    public function redirect(Request $request)
    {
        return redirect('/auth/callback?code=' . $request->query('code'));
    }

    public function callback(Request $request): RedirectResponse
    {
        $user = User::findOrFail($request->query('code'));
        Auth::login($user);
        return redirect()->route('home');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('home');
    }
}
