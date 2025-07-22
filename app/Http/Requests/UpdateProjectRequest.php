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
            'title' => ['nullable', 'max:255'],
            'description' => ['nullable'],
            'preview' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'about' => ['nullable'],
            'stack' => ['nullable', 'max:255'],
            'project_link' => ['nullable', 'url', 'starts_with:https://github.com/'],
            'presentation' => ['nullable', 'file', 'mimes:pdf,ppt,pptx', 'max:10240'],
            'video_link' => ['nullable', 'url', 'starts_with:https://vkvideo.ru/video,https://rutube.ru/video/'],
            'gallery' => ['array'],
            'gallery.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
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
