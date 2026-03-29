<?php

namespace App\Http\Controllers\Caja;

use App\Domain\Caja\Support\VentaTicketViewBuilder;
use App\Domain\Ventas\Models\Venta;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __invoke(Request $request, Venta $venta, VentaTicketViewBuilder $builder): View
    {
        return view('caja.ticket', [
            ...$builder->build($venta),
            'autoPrint' => $request->boolean('print'),
        ]);
    }
}
