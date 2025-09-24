<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProjectsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
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
