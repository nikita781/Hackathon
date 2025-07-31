<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHackathonStaffRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'staff' => ['required','array'],
            'staff.*.user_id' => ['required_with:staff', 'exists:users,id'],
            'staff.*.role_id' => ['required_with:staff', 'exists:roles,id'],
        ];
    }

    public function messages()
    {
        return trans('validations/hackathon_staff');
    }
}
