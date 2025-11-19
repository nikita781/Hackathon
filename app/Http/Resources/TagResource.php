<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tag */
class TagResource extends TranslatableResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->trans('title'),
            'slug' => $this->slug,
            'order' => $this->order,
        ];
    }
}
