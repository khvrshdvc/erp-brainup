<?php

namespace App\Http\Requests;

use App\Models\Submission;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class GradeSubmissionRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('grade', $this->submission());
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['grade', 'return'])],
            'score' => ['nullable', 'integer', 'min:0', 'max:'.$this->submission()->assignment->max_score],
            'feedback' => ['nullable', 'string', 'max:2000', Rule::requiredIf($this->isReturning())],
        ];
    }

    public function messages(): array
    {
        return [
            'score.min' => __('Baho manfiy bo‘lishi mumkin emas.'),
            'feedback.required' => __('Qayta topshirishga qaytarishda fikr-mulohaza yozish majburiy.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'score' => __('baho'),
            'feedback' => __('fikr-mulohaza'),
        ];
    }

    public function submission(): Submission
    {
        return $this->route('submission');
    }

    public function isReturning(): bool
    {
        return $this->input('action') === 'return';
    }
}
