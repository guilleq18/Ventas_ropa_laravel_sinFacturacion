<?php

namespace App\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;

class VariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:64'],
            'codigo_barras' => ['nullable', 'string', 'max:64'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
            'talle' => ['required', 'string', 'max:60'],
            'color' => ['required', 'string', 'max:60'],
        ];
    }

    public function validatedPayload(): array
    {
        return [
            'sku' => trim((string) $this->validated('sku')),
            'codigo_barras' => trim((string) ($this->validated('codigo_barras') ?? '')) ?: null,
            'precio' => $this->validated('precio'),
            'costo' => $this->validated('costo'),
            'activo' => $this->boolean('activo', true),
            'talle' => trim((string) $this->validated('talle')),
            'color' => trim((string) $this->validated('color')),
        ];
    }
}
