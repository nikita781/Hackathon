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
            'members.*.member_id' => ['required_with:members', 'exists:team_user.user_id'],
            'members.*.position_id' => ['required_with:members', 'exists:team_user.position_id'],
        ];
    }
}
