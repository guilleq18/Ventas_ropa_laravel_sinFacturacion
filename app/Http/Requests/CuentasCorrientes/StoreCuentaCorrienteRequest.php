<?php

namespace App\Http\Requests\CuentasCorrientes;

use Illuminate\Foundation\Http\FormRequest;

class StoreCuentaCorrienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'dni' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'fecha_nacimiento' => ['nullable', 'date'],
        ];
    }

    public function validatedPayload(): array
    {
        return [
            'dni' => trim((string) $this->validated('dni')),
            'nombre' => trim((string) $this->validated('nombre')),
            'apellido' => trim((string) $this->validated('apellido')),
            'telefono' => trim((string) ($this->validated('telefono') ?? '')),
            'direccion' => trim((string) ($this->validated('direccion') ?? '')),
            'fecha_nacimiento' => $this->validated('fecha_nacimiento'),
        ];
    }
}
