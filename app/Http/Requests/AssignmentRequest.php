<?php

namespace App\Http\Requests;

use App\Support\Upload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'max_score' => [
                'required', 'integer', 'min:1', 'max:1000',
                ...($this->maxScoreLocked() ? [Rule::in([$this->route('assignment')->max_score])] : []),
            ],
            'due_at' => $creating ? ['required', 'date', 'after:now'] : ['required', 'date'],
            'files' => ['nullable', 'array'],
            'files.*' => Upload::rules(),
            'remove' => ['nullable', 'array'],
            'remove.*' => ['integer'],
            'action' => ['required', Rule::in(['draft', 'publish'])],
        ];
    }

    /**
     * An assignment never holds more than Upload::MAX_ASSIGNMENT_FILES: what stays from before plus the new files.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('files') || $validator->errors()->has('remove')) {
                    return;
                }

                $kept = $this->route('assignment')?->attachments()->whereNotIn('id', $this->removedIds())->count() ?? 0;

                if ($kept + count($this->file('files', [])) > Upload::MAX_ASSIGNMENT_FILES) {
                    $validator->errors()->add('files', __('Ko‘pi bilan :max ta fayl biriktirish mumkin.', ['max' => Upload::MAX_ASSIGNMENT_FILES]));
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'files.*.mimes' => trans('validation.custom.file.mimes'),
            'files.*.max' => trans('validation.custom.file.max'),
            'files.*.uploaded' => trans('validation.custom.file.uploaded'),
            'study_group_id.required' => __('Guruhni tanlang.'),
            'study_group_id.exists' => __('Faqat o‘zingizga biriktirilgan guruhni tanlashingiz mumkin.'),
            'due_at.after' => __('Topshirish muddati hozirgi vaqtdan keyin bo‘lishi kerak.'),
            'max_score.min' => __('Maksimal ball kamida 1 bo‘lishi kerak.'),
            'max_score.max' => __('Maksimal ball 1000 dan oshmasligi kerak.'),
            'max_score.in' => __('Baholangan javoblar bor, maksimal ballni o‘zgartirib bo‘lmaydi.'),
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
            'files' => __('fayl'),
            'files.*' => __('fayl'),
        ];
    }

    /**
     * @return list<int>
     */
    public function removedIds(): array
    {
        return array_map('intval', (array) $this->input('remove', []));
    }

    private function maxScoreLocked(): bool
    {
        return $this->route('assignment')?->hasScoredSubmissions() ?? false;
    }
}
