<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_date' => ['required', 'date', 'before_or_equal:today'],
            'records' => ['required', 'array', 'min:1'],
            'records.*' => ['required', Rule::enum(AttendanceStatus::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'lesson_date' => 'dars sanasi',
            'records' => 'davomat',
        ];
    }
}
