<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team && ($this->user()?->can('updateProfile', $team) ?? false);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'members' => ['sometimes', 'array'],
            'members.*.member_id' => ['required_with:members', 'exists:users,id'],
            'members.*.position_id' => ['required_with:members', 'exists:positions,id'],
        ];
    }

    public function messages(): array
    {
        return trans('validations/team');
    }
}
