<?php

namespace App\Http\Resources;

use App\Http\Controllers\MediaController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\TextUI\Configuration\FileCollection;

/** @mixin \App\Models\Tab */
class TabResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $media = new MediaController();
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sections' => $this->whenLoaded('sections', TabSectionResource::collection($this->whenLoaded('sections'))),
            'partners' => route('hackathons.tabs.partner-images', [$this->hackathon_id, $this->resource]),
            'files' => $media->getAllHackathonFiles($this->resource, $this->hackathon_id),
        ];
    }
}
