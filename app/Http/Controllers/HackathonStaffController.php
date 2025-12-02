<?php

namespace App\Http\Controllers;

use App\Http\Requests\KickStaffRequest;
use App\Http\Requests\UpdateHackathonStaffRequest;
use App\Http\Resources\UserResource;
use App\Models\Hackathon;
use App\Models\HackathonInvite;
use App\Models\Project;
use App\Models\Role;
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

class HackathonStaffController extends Controller
{
    public function createInvite(Hackathon $hackathon): JsonResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(403);
        }

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
            return redirect()->route('hackathons.show', $hackathon)->with('error', __('invitation_expired'));
        }

        $user = auth()->user();

        if ($invite->hackathon->getAllHackathonStaff()->contains($user->id)) {
            return redirect()->route('hackathons.show', $hackathon)->with('error', __('already_staff'));
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
            return redirect()->route('hackathons.show', $hackathon)->with('error', __('already_participant'));
        }

        $invite->hackathon->users()->attach($user->id, ['role_id' => $invite->role_id]);

        $user
            ->notifications()
            ->where('data->url', route('hackathons.staff.accept-invite', [$hackathon, $invite->token]))
            ->update(['data->is_active' => false]);

        $user->assignedRole($invite->role_id);

        $invite->delete();

        return redirect()->route('hackathons.show', $hackathon)->with('status', __('now_staff'));
    }

    public function inviteUserById(Request $request, Hackathon $hackathon): Response
    {
        if (!Gate::check('update', $hackathon)) {
            abort(403);
        }

        $data = $request->validate([
            'users' => 'required|array',
            'users.*.user_id' => 'required',
            'users.*.role_id' => 'required|exists:roles,id',
        ]);

        $org = auth()->user();
        $errors = [];

        foreach ($data['users'] as $index => $user) {
            do {
                $token = Str::random(32);
            } while (HackathonInvite::where('token', $token)->exists());

            $invitedUserId = $user['user_id'];
            $invitedRoleId = $user['role_id'];

            if (is_string($invitedUserId)) {
                if (str_contains($invitedUserId, "ID")) {
                    $invitedUserId = (int) str_replace("ID", "", $invitedUserId);
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

            $invitedUserRole = Role::find($invitedRoleId);
            if (!$invitedUserRole) {
                $errors["users.$index.role_id"] = [__('role_not_found', ['role_id' => $invitedRoleId])];
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
                $errors["users.$index.user_id"] = [__('user_already_participant', ['user_nickname' => $invitedUser->nickname])];
                continue;
            }

            if ($hackathon->getAllHackathonStaff()->contains($invitedUser->id)) {
                $errors["users.$index.user_id"] = [__('user_already_participant', ['user_nickname' => $invitedUser->nickname])];
                continue;
            }

            if (HackathonInvite::where('hackathon_id', $hackathon->id)
                ->where('user_id', $invitedUserId)
                ->exists()) {
                $errors["users.$index.user_id"] = [__('invitation_already_sent', ['user_nickname' => $invitedUser->nickname])];
                continue;
            }

            $invite = HackathonInvite::create([
                'hackathon_id' => $hackathon->id,
                'user_id' => $invitedUserId,
                'role_id' => $invitedRoleId,
                'token' => $token,
                'expires_at' => now()->addDay(),
            ]);

            $invitedUser->notify(new InviteNotification([
                'title' => __('invitation_title'),
                'description' => __('invitation_description', [
                    'organizer_nickname' => $org->nickname,
                    'hackathon_title' => $hackathon->title,
                    'role_title' => $invitedUserRole->title
                ]),
                'url' => route('hackathons.staff.accept-invite', [$hackathon, $invite->token]),
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

    public function update(UpdateHackathonStaffRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(403);
        }

        $data = $request->validated();

        foreach ($data['staff'] as $user) {
            if ($hackathon->getAllHackathonStaff()->contains('id', $user['user_id'])) {
                $hackathon->users()->updateExistingPivot($user['user_id'], [
                    'role_id' => $user['role_id'],
                ]);
            }
        }

        return back()->with('status', __('staff_updated_success'));
    }

    public function kick(KickStaffRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(403);
        }

        $data = $request->validated();

        $hackathon->load('owner');

        foreach ($data['staff'] as $userId) {
            $user = User::findOrFail($userId);
            $hackathon->users()->detach($user->id);

            $user->notify(new KickNotification([
                'title' => __('kicked_from_hackathon_title', ['hackathon_title' => $hackathon->title]),
                'description' => __('kicked_from_hackathon_description') . $hackathon->owner->email,
                'send_at' => now()->toDateString(),
                'hackathon' => $hackathon,
            ]));
        }
        return back()->with('status', __('user_kicked_success'));
    }

    public function search(Request $request, Hackathon $hackathon): JsonResponse
    {
        $user = User::query()
            ->where('id', (int) $request->input('q'))
            ->orWhere('nickname', $request->input('q'))
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'user' => null,
                'canInvite' => false,
                'errors' => [__('user_not_found')]
            ]);
        }

        $canInvite = true;
        $errors = [];

        if ($hackathon->getAllHackathonStaff()->contains($user->id)) {
            $canInvite = false;
            $errors[] = __('user_already_in_hackathon', ['user_nickname' => $user->nickname]);
        }

        $hasActiveProject = $user->teams()
            ->where('hackathon_id', $hackathon->id)
            ->whereHas('projects', function ($query) {
                $query->whereIn('status', [Project::MODERATION, Project::PUBLISHED]);
            })
            ->exists();

        if ($hasActiveProject) {
            $canInvite = false;
            $errors[] = __('user_already_in_project', ['user_nickname' => $user->nickname]);
        }

        $hasInvite = HackathonInvite::where('hackathon_id', $hackathon->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($hasInvite) {
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
}
