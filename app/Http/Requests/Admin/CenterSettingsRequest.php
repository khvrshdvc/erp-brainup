<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CenterSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-center');
    }

    public function rules(): array
    {
        return [
            'center_name' => ['required', 'string', 'max:120'],
            'center_phone' => ['nullable', 'string', 'max:30'],
            'center_address' => ['nullable', 'string', 'max:255'],
            'center_hours' => ['nullable', 'string', 'max:120'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'attendance_edit_days' => ['sometimes', 'required', 'integer', 'min:0', 'max:365'],
        ];
    }

    public function attributes(): array
    {
        return [
            'center_name' => 'markaz nomi',
            'center_phone' => 'telefon',
            'center_address' => 'manzil',
            'center_hours' => 'ish vaqti',
            'academic_year' => 'o‘quv yili',
            'attendance_edit_days' => 'davomatni tahrirlash muddati',
        ];
    }
}
