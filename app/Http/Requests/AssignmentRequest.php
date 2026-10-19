<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'title' => ['required', 'string', 'max:160'],
            'study_group_id' => [
                'required',
                'integer',
                Rule::exists('study_groups', 'id')->where('teacher_id', $this->user()->id),
            ],
            'description' => ['required', 'string', 'max:2000'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'due_at' => $creating ? ['required', 'date', 'after:now'] : ['required', 'date'],
            'material' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'action' => ['required', Rule::in(['draft', 'publish'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'vazifa nomi',
            'study_group_id' => 'guruh',
            'description' => 'vazifa tavsifi',
            'max_score' => 'maksimal ball',
            'due_at' => 'topshirish muddati',
            'material' => 'fayl',
        ];
    }
}
