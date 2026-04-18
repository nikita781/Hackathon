<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Team */
class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing(['teamUsers.user', 'teamUsers.position']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'place' => $this->place,
            'is_profile_team' => $this->isProfileTeam(),
            'owner' => $this->whenLoaded('owner', function () {
                return new UserResource($this->owner);
            }),
            'users' => $this->whenLoaded('teamUsers', function () {
                return $this->teamUsers->map(function ($teamUser) {
                    return [
                        'user' => new UserResource($teamUser->user),
                        'position' => new PositionResource($teamUser->position),
                    ];
                });
            }),
            'can' => [
                'update_profile' => $request->user()?->can('updateProfile', $this->resource) ?? false,
                'delete_profile' => $request->user()?->can('deleteProfile', $this->resource) ?? false,
                'invite_profile' => $request->user()?->can('inviteProfile', $this->resource) ?? false,
                'leave_profile' => $request->user()?->can('leaveProfile', $this->resource) ?? false,
            ],
        ];
    }
}
