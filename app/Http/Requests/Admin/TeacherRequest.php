<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): Response
    {
        return $this->teacher() ? Gate::inspect('manageTeacher', $this->teacher()) : Response::allow();
    }

    public function rules(): array
    {
        $teacher = $this->teacher();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($teacher)],
            'password' => [$teacher ? 'nullable' : 'required', 'string', 'min:8'],
            'branches' => ['required', 'array', 'min:1'],
            'branches.*' => ['integer', Rule::in($this->user()->availableBranchIds())],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'ism',
            'email' => 'email',
            'phone' => 'telefon',
            'password' => 'parol',
            'branches' => 'filiallar',
        ];
    }

    private function teacher(): ?User
    {
        return $this->route('teacher');
    }
}
