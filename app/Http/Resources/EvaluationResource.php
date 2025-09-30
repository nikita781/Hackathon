<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Evaluation */
class EvaluationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'criterion' => $this->whenLoaded('criterion', new CriterionResource($this->criterion)),
            'score' => $this->score,
            'updated_at' => $this->updated_at,
        ];
    }
}
