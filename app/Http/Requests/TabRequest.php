<?php

namespace App\Http\Requests;

use App\Models\Tab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', Rule::in(Tab::TAB_TITLES)],

            'sections' => ['nullable', 'array'],
            'sections.*.id' => ['nullable', 'integer', 'exists:tab_sections,id'],
            'sections.*.title' => ['required_with:sections', 'string', 'max:255'],
            'sections.*.content' => ['nullable', 'json'],

            'sections.*.items' => ['nullable', 'array'],
            'sections.*.items.*.id' => ['nullable', 'integer', 'exists:tab_items,id'],
            'sections.*.items.*.title' => ['required_with:sections.*.items', 'string', 'max:255'],
            'sections.*.items.*.content' => ['nullable', 'string'],
            'sections.*.items.*.image_path' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5048'],

            'partners' => ['nullable', 'array'],
            'partners.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:5048'],
            'delete_media_ids' => ['nullable', 'array'],
            'delete_media_ids.*' => ['integer', 'exists:media,id'],
        ];
    }

    public function messages(): array
    {
        return trans('validations/tab');
    }
}
