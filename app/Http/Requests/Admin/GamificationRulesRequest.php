<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GamificationRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rules' => ['required', 'array'],
            'rules.*.points' => ['required', 'integer', 'min:0', 'max:1000'],
            'rules.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rules' => __('qoidalar'),
            'rules.*.points' => __('ball'),
        ];
    }
}
