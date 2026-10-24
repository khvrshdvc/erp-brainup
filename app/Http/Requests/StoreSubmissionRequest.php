<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use App\Models\Submission;
use App\Support\Upload;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreSubmissionRequest extends FormRequest
{
    private ?Submission $existing = null;

    private bool $existingLoaded = false;

    public function authorize(): Response
    {
        return Gate::inspect('submit', $this->assignment());
    }

    public function rules(): array
    {
        return [
            'files' => ['nullable', 'array'],
            'files.*' => Upload::rules(),
            'remove' => ['nullable', 'array'],
            'remove.*' => ['integer'],
            'body' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * A submission always keeps at least one file, and never more than Upload::MAX_SUBMISSION_FILES:
     * what stays from the earlier submission plus the new files.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('files') || $validator->errors()->has('remove')) {
                    return;
                }

                $total = $this->keptAttachmentCount() + count($this->file('files', []));

                if ($total === 0) {
                    $validator->errors()->add('files', __('Vazifa faylini biriktiring.'));
                } elseif ($total > Upload::MAX_SUBMISSION_FILES) {
                    $validator->errors()->add('files', __('Ko‘pi bilan :max ta fayl biriktirish mumkin.', ['max' => Upload::MAX_SUBMISSION_FILES]));
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
        ];
    }

    public function attributes(): array
    {
        return [
            'files' => __('fayl'),
            'files.*' => __('fayl'),
            'body' => __('izoh'),
        ];
    }

    /**
     * Earlier files the student keeps (the ones not ticked for removal).
     */
    public function keptAttachmentCount(): int
    {
        return $this->existingSubmission()?->attachments()->whereNotIn('id', $this->removedIds())->count() ?? 0;
    }

    /**
     * @return list<int>
     */
    public function removedIds(): array
    {
        return array_map('intval', (array) $this->input('remove', []));
    }

    public function assignment(): Assignment
    {
        return $this->route('assignment');
    }

    public function existingSubmission(): ?Submission
    {
        if (! $this->existingLoaded) {
            $this->existing = Submission::where('assignment_id', $this->assignment()->id)
                ->where('student_id', $this->user()->id)
                ->first();
            $this->existingLoaded = true;
        }

        return $this->existing;
    }
}
