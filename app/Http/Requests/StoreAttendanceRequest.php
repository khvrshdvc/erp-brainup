<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use App\Models\Setting;
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
            'lesson_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', ...$this->windowRules()],
            'records' => ['required', 'array', 'min:1'],
            'records.*' => ['required', Rule::enum(AttendanceStatus::class)],
        ];
    }

    public function messages(): array
    {
        $days = Setting::attendanceEditDays();

        return [
            'lesson_date.after_or_equal' => $days > 0
                ? __('Davomatni faqat oxirgi :days kun ichida belgilash yoki o‘zgartirish mumkin.', ['days' => $days])
                : __('Davomatni faqat bugungi sana uchun belgilash mumkin.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'lesson_date' => __('dars sanasi'),
            'records' => __('davomat'),
        ];
    }

    /**
     * Teachers can only touch recent lessons; admins are not limited.
     */
    private function windowRules(): array
    {
        if (! $this->user()->isTeacher()) {
            return [];
        }

        return ['after_or_equal:'.today()->subDays(Setting::attendanceEditDays())->toDateString()];
    }
}
