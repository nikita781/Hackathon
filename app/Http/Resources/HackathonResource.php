<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Hackathon */
class HackathonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_path' => route('hackathons.image', $this->resource),
//            'image_mobile_path' => route('hackathons.image-mobile', $this->resource),
            'format' => $this->format,
            'type' => $this->type,
            'min_team_size' => $this->min_team_size,
            'max_team_size' => $this->max_team_size,
            'registration_start' => $this->registration_start,
            'registration_end' => $this->registration_end,
            'event_start' => $this->event_start,
            'event_end' => $this->event_end,
            'prize_type' => $this->prize_type,
            'prize_pool' => $this->prize_pool,
            'slug' => $this->slug,
            'status' => $this->status,
            'published_time' => $this->published_time,
            'moderated_time' => $this->moderated_time,
            'blocked_time' => $this->blocked_time,
            'comment' => $this->comment,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'nominations' => NominationResource::collection($this->whenLoaded('nominations')),
            'criteria_groups' => CriterionGroupResource::collection($this->whenLoaded('criteriaGroups')),
            'tabs' => TabResource::collection($this->whenLoaded('tabs')),
            'awards' => AwardResource::collection($this->whenLoaded('awards')),
            'organizer' => new UserResource($this->whenLoaded('owner')),
            'projects' => ProjectResource::collection($this->whenLoaded('allProjects'))
        ];
    }
}
