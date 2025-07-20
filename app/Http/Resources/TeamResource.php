<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Team */
class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'users' => $this->whenLoaded('teamUsers', function() {
                return $this->teamUsers->map(function($teamUser) {
                    return [
                        'user' => new UserResource($teamUser->user),
                        'position' => new PositionResource($teamUser->position),
                    ];
                });
            }),
        ];
    }
}
