<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],
            'permission_ids' => ['array'],
            'permission_ids.*' => [Rule::exists('permissions', 'id')],
        ];
    }

    public function validatedPayload(): array
    {
        return [
            'name' => trim((string) $this->validated('name')),
            'permission_ids' => collect($this->validated('permission_ids', []))
                ->map(fn ($id) => (int) $id)
                ->all(),
        ];
    }
}
