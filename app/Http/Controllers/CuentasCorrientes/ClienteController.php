<?php

namespace App\Http\Controllers\CuentasCorrientes;

use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ClienteController extends Controller
{
    public function toggle(Cliente $cliente): RedirectResponse
    {
        $cliente->update([
            'activo' => ! $cliente->activo,
        ]);

        $cuenta = $cliente->cuentaCorriente;

        if ($cuenta) {
            return redirect()
                ->route('cuentas-corrientes.show', $cuenta)
                ->with('success', 'Estado del cliente actualizado.');
        }

        return redirect()
            ->route('cuentas-corrientes.index')
            ->with('success', 'Estado del cliente actualizado.');
    }
}
