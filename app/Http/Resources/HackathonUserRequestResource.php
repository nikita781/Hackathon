<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\HackathonUserRequest */
class HackathonUserRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,

            'hackathon' => $this->whenLoaded('hackathon', fn () => new HackathonResource($this->hackathon)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
        ];
    }
}
