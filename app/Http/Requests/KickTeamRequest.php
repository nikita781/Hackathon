<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KickTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'members' => ['required','array'],
            'members.*' => ['required_with:members', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return trans('validations/kick_team');
    }
}
