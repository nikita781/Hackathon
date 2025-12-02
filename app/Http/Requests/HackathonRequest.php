<?php

namespace App\Http\Requests;

use App\Models\Hackathon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'registration_start' => ['nullable', 'date', 'before:registration_end'],
            'registration_end' => ['required', 'date', 'before_or_equal:event_start'],
            'event_start' => ['required', 'date', 'before:event_end'],
            'event_end' => ['required', 'date'],
            'work_time_start' => ['required', 'date', 'after_or_equal:event_start', 'before_or_equal:event_end', 'before_or_equal:work_time_end'],
            'work_time_end'   => ['required', 'date', 'after_or_equal:work_time_start', 'before_or_equal:event_end'],
            'evaluation_start' => ['required', 'date', 'after_or_equal:work_time_end', 'before_or_equal:evaluation_end'],
            'evaluation_end' => ['required', 'date', 'after_or_equal:evaluation_start', 'before_or_equal:event_end'],
            'prize_type' => ['required', 'in:cash,non-cash'],
            'prize_pool' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $prizeType = $this->input('prize_type');

                    if ($prizeType === 'cash') {
                        if (!is_numeric($value) || $value > 10000000) {
                            $fail(__('validation_prize_pool_cash'));
                        }
                    } elseif ($prizeType === 'non-cash') {
                        if (!is_string($value) || strlen($value) > 255) {
                            $fail(__('validation_prize_pool_non_cash'));
                        }
                    }
                }
            ],
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
        return trans('validations/hackathon');
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $data = $this->validated();

                if (!empty($data['registration_end'])) {
                    $end = Carbon::parse($data['registration_end']);

                    if ($end->lessThanOrEqualTo(now())) {
                        $validator->errors()->add(
                            'registration_end',
                            __('validations/hackathon.registration_end.after')
                        );
                    }
                }
            }
        ];
    }
}
