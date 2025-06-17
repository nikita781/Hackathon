<?php

namespace App\Http\Requests;

use App\Models\Hackathon;
use App\Models\Tab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TabRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', Rule::in(Tab::TAB_TITLES)],
        ];
    }

    public function authorize(): bool
    {
        $hackathon = $this->route('hackathon');
        return Gate::check('update', $hackathon);
    }
}
