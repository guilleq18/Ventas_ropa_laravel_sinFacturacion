<?php

namespace App\Http\Controllers\Fiscal;

use App\Domain\Fiscal\Models\VentaComprobante;
use App\Domain\Fiscal\Support\VentaComprobanteViewBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComprobanteController extends Controller
{
    public function __invoke(Request $request, VentaComprobante $ventaComprobante, VentaComprobanteViewBuilder $builder): View
    {
        return view('fiscal.comprobante', [
            ...$builder->build($ventaComprobante),
            'autoPrint' => $request->boolean('print'),
        ]);
    }
}
