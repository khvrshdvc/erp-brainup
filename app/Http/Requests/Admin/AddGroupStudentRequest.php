<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddGroupStudentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', 'student')
                    ->where('status', UserStatus::Active->value)
                    ->where('branch_id', $this->route('studyGroup')->branch_id),
            ],
        ];
    }

    public function messages(): array
    {
        return ['student_id.exists' => 'Faol o‘quvchini tanlang (bu filialdagi nofaol o‘quvchini guruhga qo‘shib bo‘lmaydi).'];
    }

    public function attributes(): array
    {
        return ['student_id' => 'o‘quvchi'];
    }
}
