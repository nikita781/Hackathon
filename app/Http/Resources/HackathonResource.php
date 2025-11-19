<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Hackathon */
class HackathonResource extends TranslatableResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->trans("title"),
            'image_path' => route('hackathons.image', $this->resource),
            'format' => $this->format,
            'type' => $this->type,
            'min_team_size' => $this->min_team_size,
            'max_team_size' => $this->max_team_size,
            'registration_start' => $this->registration_start,
            'registration_end' => $this->registration_end,
            'event_start' => $this->event_start,
            'event_end' => $this->event_end,
            'work_time_start' => $this->work_time_start,
            'work_time_end' => $this->work_time_end,
            'evaluation_start' => $this->evaluation_start,
            'evaluation_end' => $this->evaluation_end,
            'is_finished' => $this->is_finished,
            'prize_type' => $this->prize_type,
            'prize_pool' => $this->trans("prize_pool"),
            'slug' => $this->slug,
            'status' => $this->status,
            'published_time' => $this->published_time,
            'moderated_time' => $this->moderated_time,
            'blocked_time' => $this->blocked_time,
            'comment' => $this->trans("comment"),

            'can' => [
                'publish' => Gate::check('publish', $this->resource),
            ],

            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'nominations' => NominationResource::collection($this->whenLoaded('nominations')),
            'criteria_groups' => CriterionGroupResource::collection($this->whenLoaded('criteriaGroups')),
            'tabs' => TabResource::collection($this->whenLoaded('tabs')),
            'awards' => AwardResource::collection($this->whenLoaded('awards')),
            'organizer' => new UserResource($this->whenLoaded('owner')),
            'projects' => ProjectResource::collection($this->whenLoaded('allProjects')),
            'users_count' => $this->users_count,
            'projects_count' => $this->all_projects_count,
            'moderation_projects_count' => $this->moderation_projects_count,
            'accepted_projects_count' => $this->accepted_projects_count,
            'rejected_projects_count' => $this->rejected_projects_count,
        ];
    }
}
