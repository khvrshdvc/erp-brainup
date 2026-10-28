<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Services\CredentialGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CredentialController extends Controller
{
    /**
     * Suggested login and password for the "Yaratish" buttons of the student and teacher forms.
     */
    public function __invoke(Request $request, CredentialGenerator $credentials): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in([Role::Student->value, Role::Teacher->value])],
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $role = Role::from($data['role']);

        return response()->json([
            'email' => $credentials->login($role, $data['name'] ?? null, $data['phone'] ?? null),
            'password' => $credentials->password($role),
        ]);
    }
}
