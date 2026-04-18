<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class StoreProfileTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createProfile', Team::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return trans('validations/team');
    }
}
