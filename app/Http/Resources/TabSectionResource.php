<?php

namespace App\Http\Resources;

use App\Models\TabSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TabSection */
class TabSectionResource extends TranslatableResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->trans('title'),
            'content' => $this->trans('content'),
            'items' => TabItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
