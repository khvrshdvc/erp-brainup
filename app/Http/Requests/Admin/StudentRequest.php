<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): Response
    {
        return $this->student() ? Gate::inspect('manageStudent', $this->student()) : Response::allow();
    }

    public function rules(): array
    {
        $student = $this->student();

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($student)],
            'password' => [$student ? 'nullable' : 'required', 'string', 'min:8'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ];

        // Branch and first group are chosen only when the student is created.
        if (! $student) {
            $rules['branch_id'] = ['required', 'integer', Rule::in($this->user()->availableBranchIds())];
            $rules['group_id'] = [
                'nullable',
                'integer',
                Rule::exists('study_groups', 'id')->where('branch_id', $this->integer('branch_id')),
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => __('ism'),
            'email' => __('email'),
            'phone' => __('telefon'),
            'password' => __('parol'),
            'birth_date' => __('tug‘ilgan sana'),
            'branch_id' => __('filial'),
            'group_id' => __('guruh'),
        ];
    }

    private function student(): ?User
    {
        return $this->route('student');
    }
}
