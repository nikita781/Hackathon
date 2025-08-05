<?php

namespace App\Http\Requests;

use App\Models\Support;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(Support::TYPES)],
            'message' => ['required', 'max:2000'],
        ];
    }
}
