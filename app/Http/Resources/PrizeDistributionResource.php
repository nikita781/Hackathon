<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PrizeDistribution */
class PrizeDistributionResource extends TranslatableResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'place' => $this->place,
            'prize' => $this->trans('prize'),
        ];
    }
}
