<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProjectsResource extends TranslatableResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->trans('title'),
            'description' => $this->trans('description'),
            'place' => $this->team->place,
            'hackathon' => [
                'slug' => $this->hackathon->slug,
                'title' => $this->hackathon->title,
            ],
            'team' => $this->whenLoaded('team', function () {
                return new TeamResource($this->team);
            }),
            'certificate_url' => route('hackathons.certificate', ['hackathon' => $this->hackathon->slug]),
        ];
    }
}
