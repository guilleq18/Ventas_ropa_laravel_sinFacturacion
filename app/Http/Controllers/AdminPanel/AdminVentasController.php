<?php

namespace App\Http\Controllers\AdminPanel;

use App\Domain\Admin\Support\AdminReportService;
use App\Domain\Core\Models\Sucursal;
use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Models\VentaPago;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminVentasController extends Controller
{
    public function index(Request $request): View
    {
        $rawFrom = $request->query('from');
        $rawTo = $request->query('to');
        $today = CarbonImmutable::today();
        $from = $rawFrom ?: ($rawTo === null ? $today->format('Y-m-d') : null);
        $to = $rawTo ?: ($rawFrom === null ? $today->format('Y-m-d') : null);
        $search = trim((string) $request->query('q', ''));
        $sucursalId = trim((string) $request->query('sucursal', ''));
        $estado = trim((string) $request->query('estado', ''));

        $query = Venta::query()
            ->with(['sucursal', 'pagos', 'comprobantePrincipal'])
            ->when($from, function (Builder $builder) use ($from): void {
                $builder->where('fecha', '>=', CarbonImmutable::parse($from)->startOfDay());
            })
            ->when($to, function (Builder $builder) use ($to): void {
                $builder->where('fecha', '<=', CarbonImmutable::parse($to)->endOfDay());
            })
            ->when(ctype_digit($sucursalId), fn (Builder $builder) => $builder->where('sucursal_id', (int) $sucursalId))
            ->when($estado !== '', fn (Builder $builder) => $builder->where('estado', $estado))
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $query) use ($search): void {
                    if (ctype_digit($search)) {
                        $query->orWhere('id', (int) $search)
                            ->orWhere('numero_sucursal', (int) $search);
                    }

                    $code = strtoupper($search);
                    if (str_starts_with($code, 'V') && ctype_digit(substr($code, 1))) {
                        $query->orWhere('numero_sucursal', (int) substr($code, 1));
                    }

                    $query->orWhereHas('sucursal', fn (Builder $branchQuery) => $branchQuery->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        $sales = $query->paginate(20)->withQueryString();
        $sales->getCollection()->transform(function (Venta $venta): Venta {
            $venta->medio_pago_ui = $this->paymentSummary($venta);

            return $venta;
        });

        return view('admin-panel.ventas.index', [
            'sales' => $sales,
            'filters' => [
                'q' => $search,
                'from' => $from ?? '',
                'to' => $to ?? '',
                'sucursal' => $sucursalId,
                'estado' => $estado,
            ],
            'sucursales' => Sucursal::query()->where('activa', true)->orderBy('nombre')->get(),
            'estados' => [
                Venta::ESTADO_BORRADOR => 'Borrador',
                Venta::ESTADO_CONFIRMADA => 'Confirmada',
                Venta::ESTADO_ANULADA => 'Anulada',
            ],
        ]);
    }

    public function show(Venta $venta, AdminReportService $reportService): View
    {
        $venta->load([
            'sucursal',
            'cliente',
            'items.variante.producto',
            'items.variante.atributos.atributo',
            'items.variante.atributos.valor',
            'pagos.plan',
            'comprobantePrincipal',
        ]);

        $items = $venta->items->map(function ($item) use ($reportService) {
            $item->nombre_admin = $reportService->buildVentaItemName($item);

            return $item;
        });
        $payments = $venta->pagos->map(function (VentaPago $payment): VentaPago {
            $payment->recargo_monto_safe = (float) ($payment->recargo_monto ?? 0);
            $payment->total_pago_admin = (float) $payment->monto + (float) $payment->recargo_monto_safe;

            return $payment;
        });

        return view('admin-panel.ventas.show', [
            'venta' => $venta,
            'items' => $items,
            'pagos' => $payments,
            'medio_pago_ui' => $this->paymentSummary($venta),
            'total_items' => $items->sum(fn ($item) => (float) $item->subtotal),
            'total_recargos' => $payments->sum(fn (VentaPago $payment) => (float) $payment->recargo_monto_safe),
            'total_pagado' => $payments->sum(fn (VentaPago $payment) => (float) $payment->total_pago_admin),
        ]);
    }

    protected function paymentSummary(Venta $venta): string
    {
        $payments = $venta->pagos instanceof Collection ? $venta->pagos : collect($venta->pagos);

        if ($payments->isEmpty()) {
            return $this->paymentLabel($venta->medio_pago);
        }

        $types = $payments->pluck('tipo')
            ->filter()
            ->unique()
            ->values();

        if ($types->count() === 1) {
            return $this->paymentLabel((string) $types->first());
        }

        $labels = $types->take(2)->map(fn (string $type) => $this->paymentLabel($type))->all();
        $extra = $types->count() - count($labels);
        $base = implode(' + ', $labels);

        if ($extra > 0) {
            $base .= " + {$extra} mas";
        }

        return "Mixto ({$base})";
    }

    protected function paymentLabel(?string $type): string
    {
        return match ($type) {
            VentaPago::TIPO_CONTADO, Venta::MEDIO_PAGO_EFECTIVO => 'Contado',
            VentaPago::TIPO_DEBITO, Venta::MEDIO_PAGO_DEBITO => 'Debito',
            VentaPago::TIPO_CREDITO, Venta::MEDIO_PAGO_CREDITO => 'Credito',
            VentaPago::TIPO_TRANSFERENCIA, Venta::MEDIO_PAGO_TRANSFERENCIA => 'Transferencia',
            VentaPago::TIPO_QR => 'QR',
            VentaPago::TIPO_CUENTA_CORRIENTE, Venta::MEDIO_PAGO_CUENTA_CORRIENTE => 'Cuenta corriente',
            Venta::MEDIO_PAGO_MIXTO => 'Mixto',
            default => (string) ($type ?: 'Sin medio'),
        };
    }
}
