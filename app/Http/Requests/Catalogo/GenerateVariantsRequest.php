<?php

namespace App\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVariantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'talles' => ['required', 'string'],
            'colores' => ['required', 'string'],
            'codigo_barras_base' => ['nullable', 'string', 'max:64'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    public function talles(): array
    {
        return collect(explode(',', (string) $this->validated('talles')))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    public function colores(): array
    {
        return collect(explode(',', (string) $this->validated('colores')))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    public function validatedPayload(): array
    {
        return [
            'codigo_barras_base' => trim((string) ($this->validated('codigo_barras_base') ?? '')),
            'precio' => (string) $this->validated('precio'),
            'costo' => (string) $this->validated('costo'),
            'activo' => $this->boolean('activo', true),
        ];
    }
}
