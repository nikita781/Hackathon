<?php

namespace App\Http\Controllers;

use App\Http\Resources\HackathonResource;
use App\Http\Resources\TeamResource;
use App\Models\Position;
use App\Models\Team;
use App\Models\TeamInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function showInvite($token): Response
    {
        $invite = TeamInvite::where('token', $token)->firstOrFail();

        if ($invite->isExpired()) {
            abort(410, 'Срок действия приглашения истёк');
        }

        $team = $invite->team->load('users');
        $hackathon = $team->hackathon->load(['tags', 'media']);

        return Inertia::render('Invites/Show', [
            'token' => $token,
            'team' => new TeamResource($team),
            'hackathon' => new HackathonResource($hackathon),
            'expires_at' => $invite->expires_at,
        ]);
    }


    public function createInvite(Team $team): JsonResponse
    {
        do {
            $token = Str::random(32);
        } while (TeamInvite::where('token', $token)->exists());

        $expired_at = Carbon::now()->addHour();

        TeamInvite::create([
            'team_id' => $team->id,
            'token' => $token,
            'expires_at' => $expired_at,
        ]);

        return response()->json([
            'url' => url("/invite/{$token}"),
            'expires_at' => $expired_at->toDateTimeString(),
        ]);
    }

    public function acceptInvite(Request $request, $token): RedirectResponse
    {
        $invite = TeamInvite::where('token', $token)->firstOrFail();

        if ($invite->isExpired()) {
            abort(410, 'Срок действия приглашения истёк');
        }

        $user = auth()->user();
        $team = $invite->team;
        $hackathon = $team->hackathon;

        if (!Gate::check('join', $hackathon)) {
            abort(404);
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

        $invite->delete();

        return redirect()->route('hackathons.show', $invite->team_id)->with('team', 'Вы вступили в команду!');
    }

//    public function inviteUser(Request $request, Team $team): JsonResponse
//    {
//        $request->validate([
//            'user_id' => 'required|exists:users,id',
//        ]);
//
//        $invitedUserId = $request->input('user_id');
//
//        if ($team->users()->where('user_id', $invitedUserId)->exists()) {
//            return response()->json(['message' => 'Пользователь уже в команде'], 400);
//        }
//
//        if (TeamInvite::where('team_id', $team->id)->where('invited_user_id', $invitedUserId)->exists()) {
//            return response()->json(['message' => 'Приглашение уже отправлено'], 400);
//        }
//
//        $invite = TeamInvite::create([
//            'team_id' => $team->id,
//            'invited_user_id' => $invitedUserId,
//            'token' => null,
//            'expires_at' => now()->addHour(),
//        ]);
//
//        Notification::create([
//            'user_id' => $invitedUserId,
//            'title' => 'Приглашение в команду',
//            'image' => '/images/invite.png',
//            'content' => "Вас пригласили в команду '{$team->title}' на хакатоне '{$team->hackathon->title}'",
//            'send_date' => now()->toDateString(),
//            'type' => NotificationType::INVITE,
//        ]);
//
//        return response()->json(['message' => 'Приглашение отправлено']);
//    }
}
