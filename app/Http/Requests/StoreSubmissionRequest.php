<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use App\Models\Submission;
use App\Support\Upload;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

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
        // A file is only optional when re-submitting work that already has one.
        $hasFile = (bool) $this->existingSubmission()?->attachments()->exists();

        return [
            'file' => [$hasFile ? 'nullable' : 'required', ...Upload::rules()],
            'body' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vazifa faylini biriktiring.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'fayl',
            'body' => 'izoh',
        ];
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
