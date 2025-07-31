<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KickStaffRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'staff' => ['required','array'],
            'staff.*.user_id' => ['required_with:staff', 'exists:users,id'],
        ];
    }
}
