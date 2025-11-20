<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Nomination */
class NominationResource extends TranslatableResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->trans('title'),
            'prize' => $this->trans('prize'),
            'count_places' => $this->distribution_count,
            'places' => PrizeDistributionResource::collection($this->whenLoaded('distribution')),
        ];
    }
}
