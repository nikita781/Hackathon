<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
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
            'preview_path' => optional($this->getFirstMedia('preview'))->getFullUrl(),
            'about' => $this->about,
            'stack' => $this->stack,
            'project_link' => $this->project_link,
            'presentation_path' => optional($this->getFirstMedia('presentation'))->getFullUrl(),
            'video_link' => $this->video_link,
            'status' => $this->status,
            'moderated_time' => $this->moderated_time,
            'published_time' => $this->published_time,
            'blocked_time' => $this->blocked_time,
            'comment' => $this->comment,
            'slug' => $this->slug,
            'avg_score' => $this->avg_score,
            'gallery' => $this->getMedia('gallery')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getFullUrl(),
                'name' => $media->name,
            ]),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource),
                'delete' => $request->user()?->can('delete', $this->resource),
                'publish' => $request->user()?->can('publish', $this->resource),
            ],
            'hackathon' => $this->whenLoaded('hackathon', fn () => new HackathonResource($this->hackathon)),
            'team' => $this->whenLoaded('team', fn () => new TeamResource($this->team)),
            ];
    }
}
