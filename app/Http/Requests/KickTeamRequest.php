<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KickTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'members' => ['required','array'],
            'members.*' => ['required_with:members', 'exists:team_user.user_id'],
        ];
    }
}
