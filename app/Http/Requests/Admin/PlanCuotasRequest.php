<?php

namespace App\Http\Requests\Admin;

use App\Domain\Ventas\Models\PlanCuotas;
use Illuminate\Foundation\Http\FormRequest;

class PlanCuotasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var PlanCuotas|null $planCuota */
        $planCuota = $this->route('planCuota');

        return [
            'tarjeta' => ['required', 'string', 'max:30'],
            'cuotas' => ['required', 'integer', 'min:1'],
            'recargo_pct' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tarjeta' => strtoupper(trim((string) $this->input('tarjeta'))),
        ]);
    }

    public function validatedPayload(): array
    {
        return [
            'tarjeta' => trim((string) $this->validated('tarjeta')),
            'cuotas' => (int) $this->validated('cuotas'),
            'recargo_pct' => $this->validated('recargo_pct'),
            'activo' => $this->boolean('activo', true),
        ];
    }
}
