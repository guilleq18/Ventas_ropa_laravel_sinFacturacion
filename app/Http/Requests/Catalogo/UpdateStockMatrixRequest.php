<?php

namespace App\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'sucursal_id' => ['required', Rule::exists('sucursales', 'id')],
            'stocks' => ['array'],
            'stocks.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function validatedStocks(): array
    {
        return collect($this->validated('stocks', []))
            ->mapWithKeys(fn ($cantidad, $varianteId) => [(int) $varianteId => (int) ($cantidad ?? 0)])
            ->all();
    }
}
