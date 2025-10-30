<?php

namespace App\Http\Requests;

use App\Models\Hackathon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HackathonUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $routeParam = $this->route('hackathon') ?? $this->route('id');
        $hackathon = null;

        if ($routeParam instanceof Hackathon) {
            $hackathon = $routeParam;
        } elseif (is_numeric($routeParam)) {
            $hackathon = Hackathon::find($routeParam);
        }

        if (!$hackathon) {
            return;
        }

        $fields = [
            'registration_start', 'registration_end',
            'event_start', 'event_end',
            'work_time_start', 'work_time_end',
            'evaluation_start', 'evaluation_end',
        ];

        $merge = [];
        foreach ($fields as $f) {
            if (!$this->has($f) && !is_null($hackathon->{$f})) {
                $val = $hackathon->{$f};
                $merge[$f] = $val instanceof Carbon ? $val->toDateTimeString() : (string)$val;
            }
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }

        $this->merge(['_hackathon_status' => $hackathon->status]);
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
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'before_or_equal:event_start'],
            'event_start' => ['nullable', 'date', 'before:event_end'],
            'event_end' => ['nullable', 'date'],
            'prize_type' => ['nullable', 'in:cash,non-cash'],
            'prize_pool' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $prizeType = $this->input('prize_type');

                    if ($prizeType === 'cash') {
                        if (!is_numeric($value) || $value > 10000000) {
                            $fail('Для денежного приза нужно число до 10 000 000.');
                        }
                    } elseif ($prizeType === 'non-cash') {
                        if (!is_string($value) || strlen($value) > 255) {
                            $fail('Для неденежного приза можно до 255 символов текста.');
                        }
                    }
                }
            ],
            'work_time_start' => ['required', 'date', 'after_or_equal:event_start', 'before_or_equal:event_end', 'before_or_equal:work_time_end'],
            'work_time_end'   => ['required', 'date', 'after_or_equal:work_time_start', 'before_or_equal:event_end'],
            'evaluation_start' => ['required', 'date', 'after_or_equal:work_time_end', 'before_or_equal:evaluation_end'],
            'evaluation_end' => ['required', 'date', 'after_or_equal:evaluation_start', 'before_or_equal:event_end'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', Rule::exists('tags', 'id')],
        ];
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
                $status = (int) $this->input('_hackathon_status', 0);

                if (in_array($status, [
                    Hackathon::STATUS_MODERATION,
                    Hackathon::STATUS_PUBLISHED
                ], true)) {
                    return;
                }

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
