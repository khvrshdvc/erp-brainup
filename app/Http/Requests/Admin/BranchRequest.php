<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('branches', 'name')->ignore($this->route('branch'))],
            'address' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'filial nomi',
            'address' => 'manzil',
            'manager_name' => 'menejer',
        ];
    }

    /**
     * Attributes ready for Branch::create() / update(); an unchecked box sends nothing.
     */
    public function payload(): array
    {
        return [...$this->validated(), 'is_active' => $this->boolean('is_active')];
    }
}
