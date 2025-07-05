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
            'project_count' => $this->project_count,
            'users_count' => $this->users_count,
            'users' => $this->whenLoaded('users', UserResource::collection($this->users))
        ];
    }
}
