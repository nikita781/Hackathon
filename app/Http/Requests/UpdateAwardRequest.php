<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAwardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'image' => ['image', 'max:2048'],
            'place' => ['nullable', 'integer'],
            'for_all' => ['boolean'],
            'system' => ['boolean'],
        ];
    }
}
