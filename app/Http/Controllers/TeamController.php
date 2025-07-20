<?php

namespace App\Http\Controllers;

use App\Http\Requests\KickTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use App\Notifications\InviteNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function update(UpdateTeamRequest $request, Hackathon $hackathon, Team $team): RedirectResponse
    {
        if (!Gate::check('update', $team)) {
            abort(404);
        }

        $team->update($request->only('title'));
        $data = $request->validated();

        foreach ($data['members'] as $member) {
            if ($team->users->contains('user_id', $member['member_id'])) {
                $team->users()->updateExistingPivot($member['member_id'], [
                    'position_id' => $member['position_id'],
                ]);
            }
        }

        return back()->with('team', 'Команда успешно обновлена');
    }

    public function kick(KickTeamRequest $request, Hackathon $hackathon, Team $team): RedirectResponse
    {
        if (!Gate::check('kick', $team)) {
            abort(404);
        }

        $data = $request->validated();

        foreach ($data['members'] as $memberId) {
            $team->users()->detach($memberId);
        }

        return back()->with('team', 'Участник команды успешно исключен');
    }
//
//    public function showInvite($token): Response
//    {
//        $invite = TeamInvite::where('token', $token)->firstOrFail();
//
//        if ($invite->isExpired()) {
//            abort(410, 'Срок действия приглашения истёк');
//        }
//
//        $team = $invite->team->load('users');
//        $hackathon = $team->hackathon->load(['tags', 'media']);
//
//        return Inertia::render('Invites/Show', [
//            'token' => $token,
//            'team' => new TeamResource($team),
//            'hackathon' => new HackathonResource($hackathon),
//            'expires_at' => $invite->expires_at,
//        ]);
//    }


    public function createInvite(Hackathon $hackathon, Team $team): JsonResponse
    {
        do {
            $token = Str::random(32);
        } while (TeamInvite::where('token', $token)->exists());

        $expired_at = Carbon::now()->addDay();

        TeamInvite::create([
            'team_id' => $team->id,
            'token' => $token,
            'expires_at' => $expired_at,
        ]);

        return response()->json([
            'url' => route('hackathons.teams.accept-invite', [$hackathon, $team, $token]),
            'expires_at' => $expired_at->toDateTimeString(),
        ]);
    }

    public function acceptInvite(Request $request, Hackathon $hackathon, Team $team, $token): RedirectResponse
    {
        $invite = TeamInvite::where('token', $token)->firstOrFail();

        if ($invite->isExpired()) {
            abort(410, 'Срок действия приглашения истёк');
        }

        $user = auth()->user();

        if (!Gate::check('join', $hackathon)) {
            abort(404);
        }

        if (!$hackathon->users()->where('user_id', $user->id)->exists()) {
            return redirect()->route('hackathons.show', $hackathon)->with('hackathon', 'Сначала вступите в хакатон');
        }

        if ($invite->team->users()->where('user_id', $user->id)->exists()) {
            abort(400, 'Вы уже в команде');
        }

        $maxSize = $hackathon->max_team_size;
        $currentCount = $team->users()->count();

        if ($currentCount >= $maxSize) {
            abort(400, 'Команда уже заполнена');
        }

        $invite->team->users()->attach($user->id, ['position_id' => Position::UNI_POSITION]);

        $user->notifications()
            ->where('data->url', route('hackathons.teams.accept-invite', [$hackathon, $team, $invite->token]))
            ->update(['is_active' => false]);

        $invite->delete();

        return redirect()->route('hackathons.show', $invite->team_id)->with('team', 'Вы вступили в команду!');
    }

    public function inviteUserById(Request $request, Hackathon $hackathon, Team $team): JsonResponse
    {
        $data = $request->validate([
            'users' => 'array',
            'users.*.user_id' => 'required|exists:users,id',
            'users.*.position_id' => 'required|exists:positions,id',
        ]);

        foreach ($data['users'] as $user) {
            do {
                $token = Str::random(32);
            } while (TeamInvite::where('token', $token)->exists());

            $invitedUserId = $user['user_id'];
            $invitedPositionId = $user['position_id'];
            $invitedUser = User::findOrFail($invitedUserId);
            $invitedUserPosition = Position::findOrFail($invitedPositionId);

            if ($team->users()->where('user_id', $invitedUserId)->exists()) {
                return response()->json(['message' => 'Пользователь «'. $invitedUser->nickname .'» уже в команде'], 400);
            }

            if (TeamInvite::where('team_id', $team->id)->where('user_id', $invitedUserId)->exists()) {
                return response()->json(['message' => 'Приглашение пользователю «' . $invitedUser->nickname . '» уже отправлено'], 400);
            }

            $invite = TeamInvite::create([
                'team_id' => $team->id,
                'user_id' => $invitedUserId,
                'position_id' => $invitedPositionId,
                'token' => $token,
                'expires_at' => now()->addDay(),
            ]);


            $invitedUser->notify(new InviteNotification([
                'title' => 'Приглашение в команду',
                'description' => "Пользователь {$invitedUser->nickname} пригласил Вас в свою команду для хакатона «{$hackathon->title}» на роль “{$invitedUserPosition->title}”.",
                'url' => route('hackathons.teams.accept-invite', [$hackathon, $team, $invite->token]),
                'send_at' => now()->toDateString(),
                'is_active' => true,
            ]));
        }

        return response()->json(['message' => 'Все отправлено']);
    }
}
