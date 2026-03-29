<?php

namespace App\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['nullable', Rule::exists('categorias', 'id')],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    public function validatedPayload(): array
    {
        return [
            'nombre' => trim((string) $this->validated('nombre')),
            'descripcion' => trim((string) ($this->validated('descripcion') ?? '')),
            'categoria_id' => $this->validated('categoria_id'),
            'activo' => $this->boolean('activo', true),
        ];
    }
}
