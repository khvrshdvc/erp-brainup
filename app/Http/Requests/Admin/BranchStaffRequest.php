<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Details of a branch admin (super admin only) or of a teacher, edited from the settings page.
 * Teachers who also work in branches the admin does not manage keep their email and password.
 */
class BranchStaffRequest extends FormRequest
{
    public function authorize(): Response
    {
        $staff = $this->staff();

        return $staff->isTeacher()
            ? Gate::inspect('manageTeacher', $staff)
            : Gate::inspect('manageBranchAdmin', $staff);
    }

    /**
     * Several of these forms share the settings page, so errors are kept per person.
     */
    protected function prepareForValidation(): void
    {
        $this->errorBag = 'staff_'.$this->staff()->id;
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.settings.edit').'#staff-'.$this->staff()->id;
    }

    public function rules(): array
    {
        $staff = $this->staff();

        $credentials = $this->canManageCredentials()
            ? [
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff)],
                'password' => ['nullable', 'string', 'min:8'],
            ]
            : ['email' => ['prohibited'], 'password' => ['prohibited']];

        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($staff)],
            ...$credentials,
        ];
    }

    public function messages(): array
    {
        return [
            'email.prohibited' => __('Bu o‘qituvchi boshqa filiallarda ham ishlaydi, uning emailini o‘zgartira olmaysiz.'),
            'password.prohibited' => __('Bu o‘qituvchi boshqa filiallarda ham ishlaydi, uning parolini o‘zgartira olmaysiz.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('ism'),
            'email' => __('email'),
            'phone' => __('telefon'),
            'password' => __('parol'),
        ];
    }

    private function canManageCredentials(): bool
    {
        $staff = $this->staff();

        return ! $staff->isTeacher() || $this->user()->can('manageTeacherCredentials', $staff);
    }

    private function staff(): User
    {
        return $this->route('staff');
    }
}
