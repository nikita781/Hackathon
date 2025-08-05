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
            'messages' => $this->whenLoaded('messages', SupportMessageResource::collection($this->messages)),
        ];
    }
}
