<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileTeamRequest;
use App\Http\Requests\UpdateProfileTeamRequest;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Position;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use App\Notifications\InviteNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ProfileTeamController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $createdTeams = $user
            ->ownedProfileTeams()
            ->with($this->relations())
            ->latest()
            ->get();

        $memberTeams = $user
            ->profileTeams()
            ->where(function ($query) use ($user) {
                $query
                    ->whereNull('teams.owner_id')
                    ->orWhere('teams.owner_id', '!=', $user->id);
            })
            ->with($this->relations())
            ->latest('teams.created_at')
            ->get();

        return response()->json([
            'createdTeams' => TeamResource::collection($createdTeams),
            'memberTeams' => TeamResource::collection($memberTeams),
        ]);
    }

    public function store(StoreProfileTeamRequest $request): JsonResponse
    {
        $this->abortIfTeamReadOnly();

        $team = DB::transaction(function () use ($request) {
            $team = Team::create([
                'owner_id' => $request->user()->id,
                'hackathon_id' => null,
                'title' => $request->validated('title'),
            ]);

            $team->users()->attach($request->user()->id, [
                'position_id' => Position::CAPITAN_POSITION,
            ]);

            return $team;
        });

        $team->load($this->relations());

        return response()->json([
            'team' => new TeamResource($team),
        ], HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateProfileTeamRequest $request, Team $team): JsonResponse
    {
        $this->abortIfTeamReadOnly();

        abort_unless($team->isProfileTeam(), 404);

        $data = $request->validated();

        $team->update([
            'title' => $data['title'],
        ]);

        foreach ($data['members'] ?? [] as $member) {
            if ($team->users()->where('users.id', $member['member_id'])->exists()) {
                $team->users()->updateExistingPivot($member['member_id'], [
                    'position_id' => $member['position_id'],
                ]);
            }
        }

        $team->load($this->relations());

        return response()->json([
            'team' => new TeamResource($team),
        ]);
    }

    public function destroy(Team $team): Response
    {
        $this->abortIfTeamReadOnly();

        abort_unless($team->isProfileTeam(), 404);
        Gate::authorize('deleteProfile', $team);

        $team->delete();

        return response()->noContent();
    }

    public function createInvite(Team $team): JsonResponse
    {
        $this->abortIfTeamReadOnly();

        abort_unless($team->isProfileTeam(), 404);
        Gate::authorize('inviteProfile', $team);

        do {
            $token = Str::random(32);
        } while (TeamInvite::where('token', $token)->exists());

        $expiresAt = Carbon::now()->addDay();

        TeamInvite::create([
            'team_id' => $team->id,
            'position_id' => Position::UNI_POSITION,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'url' => route('profile.teams.accept-invite', [$team, $token]),
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    public function acceptInvite(Request $request, Team $team, string $token): JsonResponse|RedirectResponse
    {
        $this->abortIfTeamReadOnly();

        abort_unless($team->isProfileTeam(), 404);
        Gate::authorize('acceptProfileInvite', $team);

        $invite = TeamInvite::where('team_id', $team->id)
            ->where('token', $token)
            ->firstOrFail();

        if ($invite->isExpired()) {
            return $this->inviteResponse($request, 'error', __('invitation_expired'), HttpResponse::HTTP_GONE);
        }

        if ($team->users()->where('users.id', $request->user()->id)->exists()) {
            return $this->inviteResponse($request, 'error', __('already_in_team'), HttpResponse::HTTP_CONFLICT);
        }

        $team->users()->attach($request->user()->id, [
            'position_id' => $invite->position_id ?? Position::UNI_POSITION,
        ]);

        $request->user()
            ->notifications()
            ->where('data->url', route('profile.teams.accept-invite', [$team, $invite->token]))
            ->update(['data->is_active' => false]);

        $invite->delete();

        return $this->inviteResponse($request, 'status', __('joined_team'));
    }

    public function kick(Request $request, Team $team): Response
    {
        $this->abortIfTeamReadOnly();

        abort_unless($team->isProfileTeam(), 404);
        Gate::authorize('updateProfile', $team);

        $data = $request->validate([
            'members' => ['required', 'array'],
            'members.*' => ['required', 'exists:users,id'],
        ]);

        foreach ($data['members'] as $memberId) {
            $isCaptain = $team->teamUsers()
                ->where('user_id', $memberId)
                ->where('position_id', Position::CAPITAN_POSITION)
                ->exists();

            if ($isCaptain) {
                throw ValidationException::withMessages([
                    'members' => ['Капитана команды нельзя исключить.'],
                ]);
            }

            $team->users()->detach($memberId);

            TeamInvite::where('team_id', $team->id)
                ->where('user_id', $memberId)
                ->delete();
        }

        return response()->noContent();
    }

    public function leave(Request $request, Team $team): Response
    {
        $this->abortIfTeamReadOnly();

        abort_unless($team->isProfileTeam(), 404);
        Gate::authorize('leaveProfile', $team);

        $team->users()->detach($request->user()->id);

        return response()->noContent();
    }

    public function search(Request $request, Team $team): JsonResponse
    {
        $this->abortIfTeamReadOnly();

        abort_unless($team->isProfileTeam(), 404);
        Gate::authorize('inviteProfile', $team);

        $query = trim((string) $request->input('q'));

        $user = User::query()
            ->where('id', (int) $query)
            ->orWhere('nickname', $query)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'user' => null,
                'canInvite' => false,
                'errors' => [__('user_not_found')],
            ]);
        }

        $canInvite = true;
        $errors = [];

        if ($team->users()->where('users.id', $user->id)->exists()) {
            $canInvite = false;
            $errors[] = 'Пользователь уже состоит в этой команде.';
        }

        if (TeamInvite::where('team_id', $team->id)->where('user_id', $user->id)->exists()) {
            $canInvite = false;
            $errors[] = __('user_already_invited', ['user_nickname' => $user->nickname]);
        }

        return response()->json([
            'success' => true,
            'user' => new UserResource($user),
            'canInvite' => $canInvite,
            'errors' => $errors,
        ]);
    }

    public function inviteUserById(Request $request, Team $team): Response
    {
        $this->abortIfTeamReadOnly();

        abort_unless($team->isProfileTeam(), 404);
        Gate::authorize('inviteProfile', $team);

        $data = $request->validate([
            'users' => ['required', 'array'],
            'users.*.user_id' => ['required'],
            'users.*.position_id' => ['required', 'exists:positions,id'],
        ]);

        $sender = $request->user();
        $errors = [];

        foreach ($data['users'] as $index => $user) {
            do {
                $token = Str::random(32);
            } while (TeamInvite::where('token', $token)->exists());

            $invitedUserId = $user['user_id'];
            $invitedPositionId = $user['position_id'];

            if (is_string($invitedUserId)) {
                if (str_contains($invitedUserId, 'ID')) {
                    $invitedUserId = (int) str_replace('ID', '', $invitedUserId);
                }

                if ($invitedUserId === 0 || is_string($invitedUserId)) {
                    $errors["users.$index.user_id"] = [__('user_not_found_by_id', ['user_id' => $user['user_id']])];
                    continue;
                }
            }

            $invitedUser = User::find($invitedUserId);
            if (!$invitedUser) {
                $errors["users.$index.user_id"] = [__('user_not_found_by_id', ['user_id' => $user['user_id']])];
                continue;
            }

            if ($team->users()->where('users.id', $invitedUserId)->exists()) {
                $errors["users.$index.user_id"] = ['Пользователь уже состоит в этой команде.'];
                continue;
            }

            if (TeamInvite::where('team_id', $team->id)->where('user_id', $invitedUserId)->exists()) {
                $errors["users.$index.user_id"] = [__('invitation_already_sent', ['user_nickname' => $invitedUser->nickname])];
                continue;
            }

            $position = Position::find($invitedPositionId);

            $invite = TeamInvite::create([
                'team_id' => $team->id,
                'user_id' => $invitedUserId,
                'position_id' => $invitedPositionId,
                'token' => $token,
                'expires_at' => now()->addDay(),
            ]);

            $invitedUser->notify(new InviteNotification([
                'title' => __('team_invitation_title'),
                'description' => "Пользователь {$sender->nickname} пригласил Вас в команду {$team->title} на роль {$position?->title}.",
                'url' => route('profile.teams.accept-invite', [$team, $invite->token]),
                'send_at' => now()->toDateString(),
                'is_active' => true,
                'hackathon' => null,
            ]));
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return response()->noContent();
    }

    private function relations(): array
    {
        return ['owner', 'teamUsers.user', 'teamUsers.position'];
    }

    private function inviteResponse(
        Request $request,
        string $flashKey,
        string $message,
        int $status = HttpResponse::HTTP_OK
    ): JsonResponse|RedirectResponse {
        if ($request->wantsJson()) {
            return response()->json([
                $flashKey => $message,
            ], $status);
        }

        return redirect()
            ->route('profile.my')
            ->with($flashKey, $message);
    }

    private function abortIfTeamReadOnly(): void
    {
        if (Team::isReadOnlyMode()) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'Управление командами доступно только на Foncode.');
        }
    }
}
