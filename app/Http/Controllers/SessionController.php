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
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('nickname', $credentials['login'])
            ->orWhere('email', $credentials['login'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $user = $this->syncUserFromMainSite($credentials['login']);

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return back()->withErrors([
                    'login' => 'Неверный логин или пароль',
                ]);
            }
        }

        if (!$user->hasRole(Role::MEMBER)) {
            $user->assignRole(Role::MEMBER);
        }

        Auth::login($user);

        return redirect()->route('home');
    }

    private function syncUserFromMainSite(string $login): ?User
    {
        $externalUser = DB::connection('main_site')
            ->table('users')
            ->where('name', $login)
            ->orWhere('email', $login)
            ->first();

        if (!$externalUser) {
            return null;
        }

        $user = User::where('email', $externalUser->email)->first();

        if (!$user) {
            return User::create([
                'id'        => $externalUser->id,
                'name'      => $externalUser?->fio,
                'nickname'  => $externalUser->name,
                'email'     => $externalUser->email,
                'password'  => $externalUser->password,
                'birthday'  => $externalUser?->birthday,
                'photo'     => $externalUser?->photo,
                'status'    => User::STATUS_ACTIVE,
                'created_at'=> now(),
                'updated_at'=> $externalUser->updated_at,
            ]);
        }

        $user->update([
            'name'      => $externalUser?->fio,
            'nickname'  => $externalUser->name,
            'email'     => $externalUser->email,
            'password'  => $externalUser->password,
            'birthday'  => $externalUser?->birthday,
            'photo'     => $externalUser?->photo,
            'updated_at'=> $externalUser->updated_at,
        ]);

        return User::find($user->id);
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        return redirect()->route('home');
    }
}
