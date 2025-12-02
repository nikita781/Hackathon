<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Criterion */
class CriterionResource extends TranslatableResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->trans('title'),
            'max_score' => $this->max_score,
        ];
    }
}
