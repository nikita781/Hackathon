<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SupportMessage */
class SupportMessageResource extends TranslatableResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message_type' => $this->message_type,
            'message' => $this->trans('message'),
            'creator' => $this->whenLoaded('user', new UserResource($this->user)),
        ];
    }
}
