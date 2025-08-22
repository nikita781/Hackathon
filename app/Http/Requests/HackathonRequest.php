<?php

namespace App\Http\Requests;

use App\Models\Hackathon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Lang;

class HackathonRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'min:5'],
            'image_path' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'format' => ['required', 'in:online,offline,hybrid'],
            'type' => ['required', 'in:individual,team'],
            'min_team_size' => [
                'required_if:type,team', 'integer', 'min:1', 'lte:max_team_size', 'exclude_if:type,individual'
            ],
            'max_team_size' => [
                'required_if:type,team', 'integer', 'min:1', 'gte:min_team_size', 'exclude_if:type,individual'
            ],
            'registration_start' => ['nullable', 'date', 'before_or_equal:registration_end'],
            'registration_end' => ['required', 'date', 'before_or_equal:event_start'],
            'event_start' => ['required', 'date', 'before_or_equal:event_end'],
            'event_end' => ['required', 'date'],
            'prize_type' => ['required', 'in:cash,non-cash'],
            'prize_pool' => ['required', 'numeric', 'min:0'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', Rule::exists('tags', 'id')],
        ];
    }

    public function authorize(): bool
    {
        return Gate::check('create', Hackathon::class);
    }

    public function messages(): array
    {
        return Lang::get('validations/hackathon');
    }
}
