<?php

namespace App\Http\Resources;

use App\Models\Position;
use App\Models\Role;
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

            'hackathons' => HackathonResource::collection($this->whenLoaded('hackathons')),
            'position' => new RoleResource(Role::find($this->pivot?->role_id)),
        ];
    }
}
