<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'max:255'],
            'description' => ['required'],
            'preview' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return trans('validations/project');
    }
}
