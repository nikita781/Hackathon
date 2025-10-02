<?php

namespace App\Http\Controllers;

use App\Http\Requests\KickTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use App\Notifications\InviteNotification;
use App\Notifications\KickNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(Request $request, Hackathon $hackathon): JsonResponse
    {
        if (!Gate::check('viewAll', [Team::class, $hackathon])) {
            abort(403);
        }

        $perPage = min($request->get('per_page', 10), 10);

        $paginator = $hackathon->allTeams()->filter($request)->paginate($perPage);

        return response()->json([
            'teams' => TeamResource::collection($paginator)->response()->getData(true),
            'count' => $hackathon->countTeams($request),
        ]);
    }

    public function update(UpdateTeamRequest $request, Hackathon $hackathon, Team $team): RedirectResponse
    {
        if (!Gate::check('update', $team)) {
            abort(403);
        }

        $team->update($request->only('title'));
        $data = $request->validated();

        foreach ($data['members'] as $member) {
            if ($team->users->contains('id', $member['member_id'])) {
                $team->users()->updateExistingPivot($member['member_id'], [
                    'position_id' => $member['position_id'],
                ]);
            }
        }

        return back()->with('status', 'Команда успешно обновлена');
    }

    public function kick(KickTeamRequest $request, Hackathon $hackathon, Team $team): RedirectResponse
    {
        if (!Gate::check('kick', $team)) {
            abort(403);
        }

        $data = $request->validated();

        foreach ($data['members'] as $memberId) {
            $user = User::findOrFail($memberId);

            $team->users()->detach($user->id);

            $newTeam = Team::create([
                'hackathon_id' => $hackathon->id,
                'title' => "Команда " . $user->nickname,
            ]);

            $newTeam->users()->attach($user->id, [
                'position_id' => Position::CAPITAN_POSITION,
            ]);

            $user->notify(new KickNotification([
                'title' => "Вас исключили из команды \"{$team->title}\"",
                'description' => "Теперь у вас новая команда \"{$newTeam->title}\" посмотрите в хакатоне \"{$hackathon->title}\"",
                'send_at' => now()->toDateString(),
                'hackathon' => $hackathon,
            ]));
        }

        return back()->with('status', 'Участник команды успешно исключен');
    }

    public function createInvite(Hackathon $hackathon, Team $team): JsonResponse
    {
        do {
            $token = Str::random(32);
        } while (TeamInvite::where('token', $token)->exists());

        $expired_at = Carbon::now()->addDay();

        TeamInvite::create([
            'team_id' => $team->id,
            'position_id' => Position::UNI_POSITION,
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

        if (!Gate::check('acceptInvite', $hackathon)) {
            abort(403);
        }

        if (!$hackathon->users()->where('user_id', $user->id)->exists()) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', 'Сначала вступите в хакатон');
        }

        $oldTeam = $hackathon->teams()
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->with('teamUsers')
            ->first();

        if ($oldTeam && $oldTeam->id !== $team->id) {
            $isCaptain = $oldTeam->teamUsers()
                ->where('user_id', $user->id)
                ->where('position_id', Position::CAPITAN_POSITION)
                ->exists();

            $membersCount = $oldTeam->users()->count();

            if ($membersCount === 1) {
                $oldTeam->delete();
            } elseif ($isCaptain) {
                $newCaptain = $oldTeam->teamUsers()
                    ->where('user_id', '!=', $user->id)
                    ->orderBy('created_at')
                    ->first();

                if ($newCaptain) {
                    $oldTeam->teamUsers()
                        ->where('id', $newCaptain->id)
                        ->update(['position_id' => Position::CAPITAN_POSITION]);
                }
            }

            $oldTeam->users()->detach($user->id);
        }

        if ($invite->team->users()->where('user_id', $user->id)->exists()) {
            abort(400, 'Вы уже в команде');
        }

        $maxSize = $hackathon->max_team_size;
        $currentCount = $team->users()->count();

        if ($currentCount >= $maxSize) {
            abort(400, 'Команда уже заполнена');
        }

        $invite->team->users()->attach($user->id, ['position_id' => $invite->position_id]);

        $user->notifications()
            ->where('data->url', route('hackathons.teams.accept-invite', [$hackathon, $team, $invite->token]))
            ->update(['data->is_active' => false]);

        $invite->delete();

        return redirect()->route('hackathons.show', $hackathon)->with('status', 'Вы вступили в команду!');
    }

    public function inviteUserById(Request $request, Hackathon $hackathon, Team $team): JsonResponse
    {
        $data = $request->validate([
            'users' => 'array',
            'users.*.user_id' => 'required',
            'users.*.position_id' => 'required|exists:positions,id',
        ]);

        foreach ($data['users'] as $user) {
            do {
                $token = Str::random(32);
            } while (TeamInvite::where('token', $token)->exists());

            $invitedUserId = $user['user_id'];
            $invitedPositionId = $user['position_id'];

            if (is_string($invitedUserId)) {
                if (str_contains($invitedUserId, "ID")) {
                    $invitedUserId = str_replace("ID", "", $invitedUserId);
                    $invitedUserId = (int) $invitedUserId;
                }
            }

            $invitedUser = User::findOrFail($invitedUserId);
            $invitedUserPosition = Position::findOrFail($invitedPositionId);

            if ($team->users()->where('user_id', $invitedUserId)->exists()) {
                return response()->json(['message' => 'Пользователь «'.$invitedUser->nickname.'» уже в команде'], 400);
            }

            if (TeamInvite::where('team_id', $team->id)->where('user_id', $invitedUserId)->exists()) {
                return response()->json(['message' => 'Приглашение пользователю «'.$invitedUser->nickname.'» уже отправлено'],
                    400);
            }

            $invite = TeamInvite::create([
                'team_id' => $team->id,
                'user_id' => $invitedUserId,
                'position_id' => $invitedPositionId,
                'token' => $token,
                'expires_at' => now()->addDay(),
            ]);

            $sender = auth()->user();

            $invitedUser->notify(new InviteNotification([
                'title' => 'Приглашение в команду',
                'description' => "Пользователь {$sender->nickname} пригласил Вас в свою команду для хакатона «{$hackathon->title}» на роль “{$invitedUserPosition->title}”.",
                'url' => route('hackathons.teams.accept-invite', [$hackathon, $team, $invite->token]),
                'send_at' => now()->toDateString(),
                'is_active' => true,
                'hackathon' => $hackathon,
            ]));
        }

        return response()->json(['status' => 'Все отправлено']);
    }
}
