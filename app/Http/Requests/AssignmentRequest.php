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

    /**
     * The form sends the deadline as a date plus a 24-hour time; validation works on one due_at.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('due_date')) {
            $this->merge(['due_at' => trim($this->input('due_date').' '.$this->input('due_time', '23:59'))]);
        }
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'title' => ['required', 'string', 'max:160'],
            // The group is fixed once the assignment exists.
            ...($creating ? ['study_group_id' => [
                'required',
                'integer',
                Rule::exists('study_groups', 'id')->where('teacher_id', $this->user()->id),
            ]] : []),
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
            'study_group_id.required' => __('Guruhni tanlang.'),
            'study_group_id.exists' => __('Faqat o‘zingizga biriktirilgan guruhni tanlashingiz mumkin.'),
            'due_at.after' => __('Topshirish muddati hozirgi vaqtdan keyin bo‘lishi kerak.'),
            'max_score.min' => __('Maksimal ball kamida 1 bo‘lishi kerak.'),
            'max_score.max' => __('Maksimal ball 1000 dan oshmasligi kerak.'),
            'action.*' => __('Vazifani saqlash yoki e‘lon qilish tugmasini bosing.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('vazifa nomi'),
            'study_group_id' => __('guruh'),
            'description' => __('vazifa tavsifi'),
            'max_score' => __('maksimal ball'),
            'due_at' => __('topshirish muddati'),
            'material' => __('fayl'),
        ];
    }
}
