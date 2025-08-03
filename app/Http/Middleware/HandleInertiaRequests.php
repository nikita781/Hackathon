<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user()?->load('roles');
        return array_merge(parent::share($request), [
            'flash' => function () use ($request) {
                return [
                    'error' => $request->session()->get('error'),
                    'status' => $request->session()->get('status'),
                ];
            },
            'auth' => [
                'user' => $user,
                'roles' => $user?->roles?->pluck('title'),
            ],
            'notifications' => [
                'unread' => $request->user()?->unreadNotifications()->exists(),
            ],
        ]);
    }
}
