<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CompanyDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['nullable', 'string', 'max:80'],
            'razon_social' => ['nullable', 'string', 'max:120'],
            'cuit' => ['nullable', 'string', 'max:20'],
            'condicion_fiscal' => ['nullable', 'string', 'max:40'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validatedPayload(): array
    {
        return [
            'nombre' => trim((string) ($this->validated('nombre') ?? '')),
            'razon_social' => trim((string) ($this->validated('razon_social') ?? '')),
            'cuit' => trim((string) ($this->validated('cuit') ?? '')),
            'condicion_fiscal' => trim((string) ($this->validated('condicion_fiscal') ?? '')),
            'direccion' => trim((string) ($this->validated('direccion') ?? '')),
        ];
    }
}
