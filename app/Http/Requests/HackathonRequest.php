<?php

namespace App\Http\Requests;

use App\Models\Hackathon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class HackathonRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image_path' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'format' => ['required', 'in:online,offline,hybrid'],
            'type' => ['required', 'in:individual,team'],
            'min_team_size' => ['required_if:type,team', 'integer', 'min:1', 'lte:max_team_size', 'exclude_if:type,individual'],
            'max_team_size' => ['required_if:type,team', 'integer', 'min:1', 'gte:min_team_size', 'exclude_if:type,individual'],
            'registration_start' => ['nullable', 'date', 'before_or_equal:registration_end'],
            'registration_end' => ['required', 'date', 'before_or_equal:event_start'],
            'event_start' => ['required', 'date', 'before_or_equal:event_end'],
            'event_end' => ['required', 'date'],
            'prize_pool' => ['required', 'numeric', 'min:0'],
            'is_published' => ['boolean'],
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
        return [
            'title.required' => 'Поле "Название" обязательно.',
            'image_path.required' => 'Необходимо загрузить изображение.',
            'image_path.image' => 'Файл должен быть изображением.',
            'image_path.mimes' => 'Изображение должно быть в формате: jpeg, png, jpg, webp.',
            'image_path.max' => 'Максимальный размер изображения — 2 МБ.',
            'format.required' => 'Поле "Формат" обязательно.',
            'format.in' => 'Выбранный формат недопустим.',
            'type.required' => 'Поле "Тип участия" обязательно.',
            'type.in' => 'Выбранный тип участия недопустим.',
            'min_team_size.required' => 'Минимальный размер команды обязателен.',
            'min_team_size.integer' => 'Минимальный размер команды должен быть числом.',
            'min_team_size.lte' => 'Минимальный размер команды не может превышать максимальный.',
            'min_team_size.min' => 'Минимальный размер команды не может быть отрицательным.',
            'min_team_size.exclude_if' => 'Минимальный размер команды не может быть указан для индивидуальных участников.',
            'max_team_size.required' => 'Максимальный размер команды обязателен.',
            'max_team_size.integer' => 'Максимальный размер команды должен быть числом.',
            'max_team_size.gte' => 'Максимальный размер команды не может быть меньше минимального.',
            'max_team_size.min' => 'Максимальный размер команды не может быть отрицательным.',
            'max_team_size.exclude_if' => 'Максимальный размер команды не может быть указан для индивидуальных участников.',
            'registration_start.date' => 'Дата начала регистрации должна быть корректной датой.',
            'registration_start.before_or_equal' => 'Дата начала регистрации должна быть раньше или равна дате окончания регистрации.',
            'registration_end.required' => 'Дата окончания регистрации обязательна.',
            'registration_end.date' => 'Дата окончания регистрации должна быть корректной датой.',
            'registration_end.before_or_equal' => 'Дата окончания регистрации должна быть раньше или равна дате начала события.',
            'event_start.required' => 'Дата начала события обязательна.',
            'event_start.date' => 'Дата начала события должна быть корректной.',
            'event_start.before_or_equal' => 'Дата начала события должна быть раньше или равна дате окончания события.',
            'event_end.required' => 'Дата окончания события обязательна.',
            'event_end.date' => 'Дата окончания события должна быть корректной.',
            'prize_pool.required' => 'Поле "Призовой фонд" обязательно.',
            'prize_pool.numeric' => 'Призовой фонд должен быть числом.',
            'prize_pool.min' => 'Призовой фонд не может быть отрицательным.',
            'is_published.boolean' => 'Поле публикации должно быть логическим значением.',
            'tags.array' => 'Теги должны быть массивом.',
            'tags.*.integer' => 'ID тега должен быть числом.',
            'tags.*.exists' => 'Один или несколько тегов не существуют.',
        ];
    }
}
