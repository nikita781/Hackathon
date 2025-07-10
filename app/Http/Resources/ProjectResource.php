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
            'preview_path' => route('hackathons.projects.image', $this->resource),
            'about' => $this->about,
            'stack' => $this->stack,
            'project_link' => $this->project_link,
            'presentation_path' => route('hackathons.projects.presentation', $this->resource),
            'video_link' => $this->video_link,
            'status' => $this->status,
            'moderated_time' => $this->moderated_time,
            'published_time' => $this->published_time,
            'blocked_time' => $this->blocked_time,
            'comment' => $this->comment,
            'slug' => $this->slug,
            'avg_score' => $this->avg_score,
            'gallery' => route('hackathons.projects.gallery', $this->resource),

            'hackathon' => $this->whenLoaded('hackathon', new HackathonResource($this->hackathon)),
            'team' => $this->whenLoaded('team', new TeamResource($this->team)),
        ];
    }
}
