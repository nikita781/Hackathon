<?php

namespace App\Http\Requests;

use App\Models\Hackathon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class NominationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'max:255'],
            'prize' => ['required', 'max:255'],
            'places' => ['array'],
            'places.*.place' => ['required_with:places', 'integer'],
            'places.*.prize' => ['required_with:places', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
