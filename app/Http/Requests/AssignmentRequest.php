<?php

namespace App\Http\Requests;

use App\Support\Upload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');

        return $assignment === null || $this->user()->can('manage', $assignment);
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
            'material' => ['nullable', ...Upload::rules()],
            'action' => ['required', Rule::in(['draft', 'publish'])],
        ];
    }

    public function messages(): array
    {
        return [
            'study_group_id.required' => 'Guruhni tanlang.',
            'study_group_id.exists' => 'Faqat o‘zingizga biriktirilgan guruhni tanlashingiz mumkin.',
            'due_at.after' => 'Topshirish muddati hozirgi vaqtdan keyin bo‘lishi kerak.',
            'max_score.min' => 'Maksimal ball kamida 1 bo‘lishi kerak.',
            'max_score.max' => 'Maksimal ball 1000 dan oshmasligi kerak.',
            'action.*' => 'Vazifani saqlash yoki e‘lon qilish tugmasini bosing.',
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
