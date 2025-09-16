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
            'related_id' => $this->related_id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'date_of_birth' => $this->date_of_birth,
            'phone_number' => $this->phone_number,
            'status' => $this->status,

            'hackathons' => $this->whenLoaded('hackathons', function () {
                return HackathonResource::collection($this->hackathons);
            }),

            'hackathon_role' => $this->whenPivotLoaded('hackathon_user', function () {
                return new RoleResource(Role::find($this->pivot->role_id));
            }),

            'roles' => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}
