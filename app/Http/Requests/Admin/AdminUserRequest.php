<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'username' => [
                'required',
                'string',
                'max:80',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'sucursal_id' => ['nullable', Rule::exists('sucursales', 'id')],
            'role_ids' => ['array'],
            'role_ids.*' => [Rule::exists('roles', 'id')],
        ];
    }

    public function validatedPayload(): array
    {
        return [
            'username' => trim((string) $this->validated('username')),
            'first_name' => trim((string) ($this->validated('first_name') ?? '')),
            'last_name' => trim((string) ($this->validated('last_name') ?? '')),
            'email' => trim((string) $this->validated('email')),
            'password' => trim((string) ($this->validated('password') ?? '')),
            'is_active' => $this->boolean('is_active', true),
            'sucursal_id' => $this->validated('sucursal_id'),
            'role_ids' => collect($this->validated('role_ids', []))
                ->map(fn ($id) => (int) $id)
                ->all(),
        ];
    }
}
