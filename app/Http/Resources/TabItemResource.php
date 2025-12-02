<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TabItem */
class TabItemResource extends TranslatableResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->trans('title'),
            'content' => $this->trans('content'),
            'image_url' => $this->getFirstMediaUrl('image'),
        ];
    }
}
