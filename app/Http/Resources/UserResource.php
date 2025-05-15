<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'password' => $this->password,
            'remember_token' => $this->remember_token,
            'notifications_count' => $this->notifications_count,
            'read_notifications_count' => $this->read_notifications_count,
            'unread_notifications_count' => $this->unread_notifications_count,

            'hackathons' => HackathonCollection::collection($this->whenLoaded('hackathons')),
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
            'projectsAsCapitan' => ProjectResource::collection($this->whenLoaded('projectsAsCapitan')),
            'position' => new PositionResource(optional($this->pivot)->position),

        ];
    }
}
