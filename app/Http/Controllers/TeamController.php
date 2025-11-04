<?php

namespace App\Http\Controllers;

use App\Http\Requests\KickTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use App\Notifications\InviteNotification;
use App\Notifications\KickNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

            if ($user->teams()->where('hackathon_id', $hackathon->id)->count() !== 0) {
                return back()->with('error', 'Пользователь уже в другой команде');
            }

            $newTeam = Team::create([
                'hackathon_id' => $hackathon->id,
                'title' => 'Команда ' . $user->nickname,
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
            return redirect()->route('hackathons.show', $hackathon)->with('error', 'Срок действия приглашения истёк');
        }

        $user = auth()->user();

        if (!Gate::check('acceptInvite', $hackathon)) {
            abort(403);
        }

        if (!$hackathon->users()->where('user_id', $user->id)->exists()) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', 'Сначала вступите в хакатон');
        }

        $oldTeam = $hackathon
            ->teams()
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->with('teamUsers')
            ->first();

        if ($oldTeam && $oldTeam->id !== $team->id) {
            $isCaptain = $oldTeam
                ->teamUsers()
                ->where('user_id', $user->id)
                ->where('position_id', Position::CAPITAN_POSITION)
                ->exists();

            $membersCount = $oldTeam->users()->count();

            if ($membersCount === 1) {
                $oldTeam->delete();
            } elseif ($isCaptain) {
                $newCaptain = $oldTeam
                    ->teamUsers()
                    ->where('user_id', '!=', $user->id)
                    ->orderBy('created_at')
                    ->first();

                if ($newCaptain) {
                    $oldTeam
                        ->teamUsers()
                        ->where('id', $newCaptain->id)
                        ->update(['position_id' => Position::CAPITAN_POSITION]);
                }
            }

            $oldTeam->users()->detach($user->id);
        }

        if ($invite->team->users()->where('user_id', $user->id)->exists()) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', 'Вы уже в команде');
        }

        $maxSize = $hackathon->max_team_size;
        $currentCount = $team->users()->count();

        if ($currentCount >= $maxSize) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', 'Команда уже заполнена');
        }

        $invite->team->users()->attach($user->id, ['position_id' => $invite->position_id]);

        $user
            ->notifications()
            ->where('data->url', route('hackathons.teams.accept-invite', [$hackathon, $team, $invite->token]))
            ->update(['data->is_active' => false]);

        $invite->delete();

        return redirect()->route('hackathons.show', $hackathon)->with('status', 'Вы вступили в команду!');
    }

    public function inviteUserById(Request $request, Hackathon $hackathon, Team $team): Response
    {
        $data = $request->validate([
            'users' => 'array',
            'users.*.user_id' => 'required',
            'users.*.position_id' => 'required|exists:positions,id',
        ]);

        $errors = [];

        foreach ($data['users'] as $index => $user) {
            do {
                $token = Str::random(32);
            } while (TeamInvite::where('token', $token)->exists());

            $invitedUserId = $user['user_id'];
            $invitedPositionId = $user['position_id'];

            if (is_string($invitedUserId)) {
                if (str_contains($invitedUserId, "ID")) {
                    $invitedUserId = (int) str_replace("ID", "", $invitedUserId);
                }

                if ($invitedUserId === 0 || is_string($invitedUserId)) {
                    $errors["users.$index.user_id"] = ["Пользователь с ID «{$user['user_id']}» не найден"];
                    continue;
                }
            }

            $invitedUser = User::find($invitedUserId);
            if (!$invitedUser) {
                $errors["users.$index.user_id"] = ["Пользователь с ID «{$user['user_id']}» не найден"];
                continue;
            }

            $invitedUserPosition = Position::find($invitedPositionId);
            if (!$invitedUserPosition) {
                $errors["users.$index.position"] = ["Позиция с ID «{$invitedPositionId}» не найдена"];
                continue;
            }

            if (
                $invitedUser->teams()
                    ->where('hackathon_id', $hackathon->id)
                    ->whereHas('projects', function ($query) {
                        $query->whereIn('status', [Project::MODERATION, Project::PUBLISHED]);
                    })
                    ->exists()
            ) {
                $errors["users.$index.user_id"] = ["Пользователь «{$invitedUser->nickname}» уже является участником хакатона"];
                continue;
            }

            if ($team->users()->where('user_id', $invitedUserId)->exists()) {
                $errors["users.$index.user_id"] = ['Пользователь «' . $invitedUser->nickname . '» уже в команде'];
                continue;
            }

            if ($hackathon->getAllHackathonStaff()->contains($invitedUser->id)) {
                $errors["users.$index.user_id"] = ["Пользователь «{$invitedUser->nickname}» уже в является персоналом хакатона"];
                continue;
            }

            if (TeamInvite::where('team_id', $team->id)->where('user_id', $invitedUserId)->exists()) {
                $errors["users.$index.user_id"] = ["Приглашение пользователю «". $invitedUser->nickname . "» уже отправлено"];
                continue;
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
                'description' => "Пользователь {$sender->nickname} пригласил Вас в свою команду для хакатона «{$hackathon->title}» на роль «{$invitedUserPosition->title}».",
                'url' => route('hackathons.teams.accept-invite', [$hackathon, $team, $invite->token]),
                'send_at' => now()->toDateString(),
                'is_active' => true,
                'hackathon' => $hackathon,
            ]));
        }


        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return response()->noContent();
    }

    public function search(Request $request, Hackathon $hackathon, Team $team): JsonResponse
    {
        Gate::authorize('update', $team);

        $user = User::query()
            ->where('id', (int) $request->input('q'))
            ->orWhere('nickname', $request->input('q'))
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'user' => null,
                'canInvite' => false,
                'errors' => ["Пользователь не найден"]
            ]);
        }

        $errors = [];
        $canInvite = true;

        $isAlreadyInHackathon = $user->teams()
            ->where('hackathon_id', $hackathon->id)
            ->whereHas('projects', function ($query) {
                $query->whereIn('status', [Project::MODERATION, Project::PUBLISHED]);
            })
            ->exists();

        if ($isAlreadyInHackathon) {
            $canInvite = false;
            $errors[] = "Пользователь «{$user->nickname}» уже является участником хакатона";
        }

        if ($team->users()->where('user_id', $user->id)->exists()) {
            $canInvite = false;
            $errors[] = "Пользователь «{$user->nickname}» уже в команде";
        }

        if ($hackathon->getAllHackathonStaff()->contains($user->id)) {
            $canInvite = false;
            $errors[] = "Пользователь «{$user->nickname}» является персоналом хакатона";
        }

        $alreadyInvited = TeamInvite::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyInvited) {
            $canInvite = false;
            $errors[] = "Приглашение пользователю «{$user->nickname}» уже отправлено";
        }

        return response()->json([
            'success' => true,
            'user' => new UserResource($user),
            'canInvite' => $canInvite,
            'errors' => $errors,
        ]);
    }
}
