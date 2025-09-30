<?php

namespace App\Http\Controllers;

use App\Http\Requests\KickStaffRequest;
use App\Http\Requests\UpdateHackathonStaffRequest;
use App\Models\Hackathon;
use App\Models\HackathonInvite;
use App\Models\Role;
use App\Models\User;
use App\Notifications\InviteNotification;
use App\Notifications\KickNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class HackathonStaffController extends Controller
{
    public function createInvite(Hackathon $hackathon): JsonResponse
    {
        do {
            $token = Str::random(32);
        } while (HackathonInvite::where('token', $token)->exists());

        $expired_at = Carbon::now()->addDay();

        HackathonInvite::create([
            'hackathon_id' => $hackathon->id,
            'role_id' => Role::MENTOR,
            'token' => $token,
            'expires_at' => $expired_at,
        ]);

        return response()->json([
            'url' => route('hackathons.staff.accept-invite', [$hackathon, $token]),
            'expires_at' => $expired_at->toDateTimeString(),
        ]);
    }

    public function acceptInvite(Request $request, Hackathon $hackathon, $token): RedirectResponse
    {
        $invite = HackathonInvite::where('token', $token)->firstOrFail();

        if ($invite->isExpired()) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', 'Срок действия приглашения истёк');
        }

        $user = auth()->user();

        if ($invite->hackathon->getAllHackathonStaff()->contains($user->id)) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', 'Вы уже персонал хакатона');
        }

        if ($invite
            ->hackathon
            ->users()
            ->with('roles')
            ->withPivot('role_id')
            ->wherePivotIn('role_id', [Role::MEMBER])
            ->get()
            ->contains($user->id)
        ) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', 'Вы уже участник хакатона');
        }

        $invite->hackathon->users()->attach($user->id, ['role_id' => $invite->role_id]);

        $user
            ->notifications()
            ->where('data->url', route('hackathons.staff.accept-invite', [$hackathon, $invite->token]))
            ->update(['data->is_active' => false]);

        $user->assignedRole($invite->role_id);

        $invite->delete();

        return redirect()->route('hackathons.show', $hackathon)->with('status', 'Теперь вы персонал хакатона!');
    }

    public function inviteUserById(Request $request, Hackathon $hackathon): JsonResponse
    {
        $data = $request->validate([
            'users' => 'required|array',
            'users.*.user_id' => 'required|exists:users,id',
            'users.*.role_id' => 'required|exists:roles,id',
        ]);

        foreach ($data['users'] as $user) {
            do {
                $token = Str::random(32);
            } while (HackathonInvite::where('token', $token)->exists());

            $invitedUserId = $user['user_id'];
            $invitedRoleId = $user['role_id'];
            $invitedUser = User::findOrFail($invitedUserId);
            $invitedUserRole = Role::findOrFail($invitedRoleId);

            if ($hackathon->getAllHackathonStaff()->contains($invitedUser->id)) {
                return response()->json(['message' => 'Пользователь «' . $invitedUser->nickname . '» уже в команде'], 400);
            }

            if (HackathonInvite::where('hackathon_id', $hackathon->id)->where('user_id', $invitedUserId)->exists()) {
                return response()->json(['message' => 'Приглашение пользователю «' . $invitedUser->nickname . '» уже отправлено'], 400);
            }

            $invite = HackathonInvite::create([
                'hackathon_id' => $hackathon->id,
                'user_id' => $invitedUserId,
                'role_id' => $invitedRoleId,
                'token' => $token,
                'expires_at' => now()->addDay(),
            ]);

            $invitedUser->notify(new InviteNotification([
                'title' => 'Приглашение на хакатон от организатора',
                'description' => "Организатор хакатонов {$invitedUser->nickname} пригласил Вас на свой хакатон «{$hackathon->title}» на роль “{$invitedUserRole->title}”.",
                'url' => route('hackathons.staff.accept-invite', [$hackathon, $invite->token]),
                'send_at' => now()->toDateString(),
                'is_active' => true,
                'hackathon' => $hackathon,
            ]));
        }

        return response()->json(['status' => 'Все отправлено']);
    }

    public function update(UpdateHackathonStaffRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $data = $request->validated();

        foreach ($data['staff'] as $user) {
            if ($hackathon->getAllHackathonStaff()->contains('id', $user['user_id'])) {
                $hackathon->users()->updateExistingPivot($user['user_id'], [
                    'role_id' => $user['role_id'],
                ]);
            }
        }

        return back()->with('status', 'Персонал успешно обновлен');
    }

    public function kick(KickStaffRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $data = $request->validated();

        $hackathon->load('owner');

        foreach ($data['staff'] as $userId) {
            $user = User::findOrFail($userId);
            $hackathon->users()->detach($user->id);

            $user->notify(new KickNotification([
                'title' => "Вас исключили из хакатона \"{$hackathon->title}\"",
                'description' => "Если произошла ошибка напишите организатору на почту: {$hackathon->owner->email}",
                'send_at' => now()->toDateString(),
                'hackathon' => $hackathon,
            ]));
        }
        return back()->with('status', 'Пользователь успешно исключен с хакатона');
    }
}
