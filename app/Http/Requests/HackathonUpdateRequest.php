<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HackathonUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'format' => ['nullable', 'in:online,offline,hybrid'],
            'type' => ['nullable', 'in:individual,team'],
            'min_team_size' => ['exclude_if:type,individual', 'nullable', 'integer', 'min:1', 'lte:max_team_size'],
            'max_team_size' => ['exclude_if:type,individual', 'nullable', 'integer', 'min:1', 'gte:min_team_size'],
            'registration_start' => ['nullable', 'date', 'before_or_equal:registration_end'],
            'registration_end' => ['nullable', 'date', 'before_or_equal:event_start'],
            'event_start' => ['nullable', 'date', 'before_or_equal:event_end'],
            'event_end' => ['nullable', 'date'],
            'prize_type' => ['nullable', 'in:cash,non-cash'],
            'prize_pool' => ['nullable', 'numeric', 'min:0'],
            'work_time_start' => ['nullable', 'date'],
            'work_time_end' => ['nullable', 'date'],
            'evaluation_start' => ['nullable', 'date'],
            'evaluation_end' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', Rule::exists('tags', 'id')],
        ];
    }

    public function messages(): array
    {
        return trans('validations/hackathon');
    }
}
