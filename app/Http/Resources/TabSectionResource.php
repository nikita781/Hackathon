<?php

namespace App\Http\Resources;

use App\Models\TabSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TabSection */
class TabSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'items' => $this->whenLoaded('items', TabItemResource::collection($this->items)),
        ];
    }
}
