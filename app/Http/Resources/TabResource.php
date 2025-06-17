<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\TextUI\Configuration\FileCollection;

/** @mixin \App\Models\Tab */
class TabResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sections' => $this->whenLoaded('sections', TabSectionResource::collection($this->whenLoaded('sections'))),
            'partners' => route('hackathons.partner-images', [$this->hackathon_id, $this->resource]),
//            'files' => route('hackathons.files', $this->resource),
        ];
    }
}
