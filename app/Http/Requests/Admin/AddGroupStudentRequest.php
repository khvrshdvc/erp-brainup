<?php

namespace App\Http\Requests\Admin;

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
                    ->where('branch_id', $this->route('studyGroup')->branch_id),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['student_id' => 'o‘quvchi'];
    }
}
