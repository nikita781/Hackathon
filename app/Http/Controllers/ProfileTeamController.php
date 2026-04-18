<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileTeamRequest;
use App\Http\Requests\UpdateProfileTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Position;
use App\Models\Team;
use App\Models\TeamInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
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
        $team->update($request->validated());
        $team->load($this->relations());

        return response()->json([
            'team' => new TeamResource($team),
        ]);
    }

    public function destroy(Team $team): Response
    {
        Gate::authorize('deleteProfile', $team);

        $team->delete();

        return response()->noContent();
    }

    public function createInvite(Team $team): JsonResponse
    {
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

        $invite->delete();

        return $this->inviteResponse($request, 'status', __('joined_team'));
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
}
