<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Award */
class AwardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'place' => $this->place,
            'for_all' => $this->for_all,
            'system' => $this->system,
            'image' => route('awards.media', $this->resource),
        ];
    }
}
