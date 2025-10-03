<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255', 'min:5'],
            'description' => ['nullable', 'string', 'min:10'],
            'preview' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2000'],
            'about' => ['nullable', 'string', 'min:10'],
            'stack' => ['nullable', 'max:255'],
            'project_link' => ['nullable', 'url', 'starts_with:https://github.com/'],
            'presentation' => ['nullable', 'file', 'mimes:pdf,ppt,pptx', 'max:8000'],
            'video_link' => ['nullable', 'url', 'starts_with:https://vkvideo.ru/video,https://rutube.ru/video/'],
            'gallery' => ['array'],
            'gallery.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2000'],
            'status' => ['nullable', Rule::in(Project::PROJECT_STATUS)],

            'delete_media_ids' => ['nullable', 'array'],
            'delete_media_ids.*' => ['integer', 'exists:media,id'],
        ];
    }

    public function messages(): array
    {
        return trans('validations/project');
    }
}
