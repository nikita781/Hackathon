<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'max:255'],
            'members' => ['required','array'],
            'members.*.member_id' => ['required_with:members', 'exists:users,id'],
            'members.*.position_id' => ['required_with:members', 'exists:positions,id'],
        ];
    }

    public function messages(): array
    {
        return trans('validations/team');
    }
}
