<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Support */
class SupportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'is_completed' => $this->is_completed,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'messages' => SupportMessageResource::collection($this->whenLoaded('messages')),
            'hackathon' => new HackathonResource($this->whenLoaded('hackathon')),
            'creator' =>  new UserResource($this->whenLoaded('creator')),
        ];
    }
}
