<?php

namespace App\Http\Requests\Catalogo;

use App\Domain\Catalogo\Models\Categoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Categoria|null $categoria */
        $categoria = $this->route('categoria');

        return [
            'nombre' => [
                'required',
                'string',
                'max:80',
                Rule::unique('categorias', 'nombre')->ignore($categoria?->id),
            ],
            'activa' => ['nullable', 'boolean'],
        ];
    }

    public function validatedPayload(): array
    {
        return [
            'nombre' => trim((string) $this->validated('nombre')),
            'activa' => $this->boolean('activa', true),
        ];
    }
}
