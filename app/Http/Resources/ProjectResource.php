<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Project */
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'preview_path' => $this->preview_path ? Storage::url($this->preview_path) : null,
            'about' => $this->about,
            'stack' => $this->stack,
            'project_link' => $this->project_link,
            'presentation_path' => $this->presentation_path ? Storage::url($this->presentation_path) : null,
            'video_link' => $this->video_link,
            'is_published' => $this->is_published,

            'hackathon' => new HackathonResource($this->whenLoaded('hackathon')),
        ];
    }
}
