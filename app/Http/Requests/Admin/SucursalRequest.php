<?php

namespace App\Http\Requests\Admin;

use App\Domain\Core\Models\Sucursal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Sucursal|null $sucursal */
        $sucursal = $this->route('sucursal');

        return [
            'nombre' => [
                'required',
                'string',
                'max:80',
                Rule::unique('sucursales', 'nombre')->ignore($sucursal?->id),
            ],
            'direccion' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'activa' => ['nullable', 'boolean'],
        ];
    }

    public function validatedPayload(): array
    {
        return [
            'nombre' => trim((string) $this->validated('nombre')),
            'direccion' => trim((string) ($this->validated('direccion') ?? '')) ?: null,
            'telefono' => trim((string) ($this->validated('telefono') ?? '')) ?: null,
            'activa' => $this->boolean('activa', true),
        ];
    }
}
