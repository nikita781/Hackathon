<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KickStaffRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'staff' => ['required','array'],
            'staff.*' => ['required_with:staff', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return trans('validations/kick_staff');
    }
}
