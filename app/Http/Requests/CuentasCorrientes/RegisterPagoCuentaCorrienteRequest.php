<?php

namespace App\Http\Requests\CuentasCorrientes;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPagoCuentaCorrienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'observacion' => ['nullable', 'string'],
            'ventas' => ['required', 'array', 'min:1'],
            'ventas.*' => ['integer', 'distinct'],
        ];
    }

    public function validatedPayload(): array
    {
        return [
            'monto' => $this->validated('monto'),
            'referencia' => trim((string) ($this->validated('referencia') ?? '')),
            'observacion' => trim((string) ($this->validated('observacion') ?? '')),
            'ventas' => collect($this->validated('ventas'))
                ->map(fn (mixed $saleId) => (int) $saleId)
                ->values()
                ->all(),
        ];
    }
}
