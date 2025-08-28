<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerSupportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'max:2000'],
        ];
    }
}
