<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerSupportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'support_id' => ['required', 'exists:supports,id'],
            'message' => ['required', 'max:2000'],
        ];
    }
}
