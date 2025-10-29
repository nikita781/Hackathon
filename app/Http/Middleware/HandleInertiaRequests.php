<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Models\Hackathon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

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

    public function handle(Request $request, Closure $next): Response
    {
        $response = parent::handle($request, $next);

        $vary = $response->headers->get('Vary');

        if ($vary) {
            $varyValues = array_map('trim', explode(',', $vary));
            if (!in_array('X-Inertia', $varyValues)) {
                $varyValues[] = 'X-Inertia';
            }
            if (!in_array('Accept-Encoding', $varyValues)) {
                $varyValues[] = 'Accept-Encoding';
            }
            $response->headers->set('Vary', implode(', ', $varyValues));
        } else {
            $response->headers->set('Vary', 'Accept-Encoding, X-Inertia');
        }

        return $response;
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
                'user' => $user ? new UserResource($user) : null,
                'roles' => $user?->roles?->pluck('title'),
            ],
            'notifications' => [
                'unread' => $request->user()?->unreadNotifications()->exists(),
            ],
            'can' => [
                'admin' => Gate::check('moderate', Hackathon::class),
                'top.admin' => Gate::check('admin', Hackathon::class),
            ],
        ]);
    }
}
