<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAwardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'hackathon_id' => ['nullable', 'exists:hackathons,id'],
            'title' => ['required', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'image' => ['image', 'max:2048'],
            'place' => ['nullable', 'integer'],
            'for_all' => ['boolean'],
            'system' => ['boolean'],
        ];
    }
}
