<?php

namespace App\Domain\Admin\Support;

use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Models\VentaItem;
use App\Domain\Ventas\Models\VentaPago;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AdminReportService
{
    public function dashboardData(): array
    {
        $today = CarbonImmutable::today();
        $salesTodayQuery = Venta::query()
            ->where('estado', Venta::ESTADO_CONFIRMADA)
            ->whereDate('fecha', $today);
        $confirmedSalesToday = (clone $salesTodayQuery)
            ->with('sucursal')
            ->orderByDesc('fecha')
            ->limit(5)
            ->get();

        return [
            'ventas_hoy_total' => (string) ((clone $salesTodayQuery)->sum('total') ?: '0'),
            'ventas_hoy_cantidad' => (clone $salesTodayQuery)->count(),
            'ventas_recientes' => $confirmedSalesToday,
            'ventas_confirmadas_total' => Venta::query()->where('estado', Venta::ESTADO_CONFIRMADA)->count(),
        ];
    }

    public function balancesData(?string $from, ?string $to, string $view): array
    {
        [
            'from' => $dateFrom,
            'to' => $dateTo,
            'defaulted_to_latest_available' => $defaultedToLatestAvailable,
            'latest_available_date' => $latestAvailableDate,
        ] = $this->resolveBalancesRange($from, $to);

        $sales = Venta::query()
            ->where('estado', Venta::ESTADO_CONFIRMADA)
            ->whereBetween('fecha', [$dateFrom, $dateTo])
            ->orderBy('fecha')
            ->with(['sucursal', 'pagos', 'items.variante.producto.categoria'])
            ->get();

        $total = $sales->sum(fn (Venta $venta) => (float) $venta->total);
        $cantidad = $sales->count();
        $ticketPromedio = $cantidad > 0 ? $total / $cantidad : 0;

        $byDay = $sales->groupBy(fn (Venta $venta) => $venta->fecha?->format('d/m/Y') ?? '-');
        $labelsDay = $byDay->keys()->values();
        $dataDayTotal = $byDay->map(fn (Collection $group) => round($group->sum(fn (Venta $venta) => (float) $venta->total), 2))->values();
        $dataDayCount = $byDay->map(fn (Collection $group) => $group->count())->values();

        $byBranch = $sales->groupBy(fn (Venta $venta) => $venta->sucursal?->nombre ?? 'Sin sucursal');
        $labelsBranch = $byBranch->keys()->values();
        $dataBranchTotal = $byBranch->map(fn (Collection $group) => round($group->sum(fn (Venta $venta) => (float) $venta->total), 2))->values();
        $dataBranchCount = $byBranch->map(fn (Collection $group) => $group->count())->values();

        $hourBuckets = collect(range(0, 23))->mapWithKeys(fn (int $hour) => [str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00' => ['total' => 0, 'count' => 0]]);
        foreach ($sales as $sale) {
            $bucket = $sale->fecha?->format('H:00');
            if ($bucket && $hourBuckets->has($bucket)) {
                $current = $hourBuckets->get($bucket);
                $current['total'] += (float) $sale->total;
                $current['count']++;
                $hourBuckets->put($bucket, $current);
            }
        }

        $paymentTotals = [];
        foreach ($sales as $sale) {
            if ($sale->pagos->isEmpty()) {
                $label = $sale->medio_pago ?: 'SIN_MEDIO';
                $paymentTotals[$label]['total'] = ($paymentTotals[$label]['total'] ?? 0) + (float) $sale->total;
                $paymentTotals[$label]['count'] = ($paymentTotals[$label]['count'] ?? 0) + 1;
                continue;
            }

            foreach ($sale->pagos as $payment) {
                $label = $payment->tipo ?: 'SIN_TIPO';
                $paymentTotals[$label]['total'] = ($paymentTotals[$label]['total'] ?? 0)
                    + (float) $payment->monto
                    + (float) ($payment->recargo_monto ?? 0);
                $paymentTotals[$label]['count'] = ($paymentTotals[$label]['count'] ?? 0) + 1;
            }
        }
        uasort($paymentTotals, fn (array $a, array $b) => ($b['total'] <=> $a['total']));

        $categoryTotals = [];
        $productTotals = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $category = $item->variante?->producto?->categoria?->nombre ?? 'Sin categoria';
                $product = $item->variante?->producto?->nombre ?? 'Sin nombre';

                $categoryTotals[$category]['total'] = ($categoryTotals[$category]['total'] ?? 0) + (float) $item->subtotal;
                $categoryTotals[$category]['cantidad'] = ($categoryTotals[$category]['cantidad'] ?? 0) + (int) $item->cantidad;
                $productTotals[$product]['total'] = ($productTotals[$product]['total'] ?? 0) + (float) $item->subtotal;
                $productTotals[$product]['cantidad'] = ($productTotals[$product]['cantidad'] ?? 0) + (int) $item->cantidad;
            }
        }
        uasort($categoryTotals, fn (array $a, array $b) => ($b['total'] <=> $a['total']));
        uasort($productTotals, fn (array $a, array $b) => ($b['total'] <=> $a['total']));

        return [
            'from' => $dateFrom->format('Y-m-d'),
            'to' => $dateTo->format('Y-m-d'),
            'defaulted_to_latest_available' => $defaultedToLatestAvailable,
            'latest_available_date' => $latestAvailableDate?->format('Y-m-d'),
            'view' => in_array($view, ['ventas', 'productos', 'pagos'], true) ? $view : 'ventas',
            'total' => round($total, 2),
            'cantidad' => $cantidad,
            'ticket_promedio' => round($ticketPromedio, 2),
            'labels_day' => $labelsDay->all(),
            'data_day_total' => $dataDayTotal->all(),
            'data_day_count' => $dataDayCount->all(),
            'labels_branch' => $labelsBranch->all(),
            'data_branch_total' => $dataBranchTotal->all(),
            'data_branch_count' => $dataBranchCount->all(),
            'labels_hour' => $hourBuckets->keys()->values()->all(),
            'data_hour_total' => $hourBuckets->map(fn (array $bucket) => round($bucket['total'], 2))->values()->all(),
            'data_hour_count' => $hourBuckets->map(fn (array $bucket) => $bucket['count'])->values()->all(),
            'labels_payment' => array_keys($paymentTotals),
            'data_payment_total' => array_map(fn (array $row) => round($row['total'], 2), array_values($paymentTotals)),
            'data_payment_count' => array_map(fn (array $row) => $row['count'], array_values($paymentTotals)),
            'labels_category' => array_slice(array_keys($categoryTotals), 0, 12),
            'data_category_total' => array_map(fn (array $row) => round($row['total'], 2), array_slice(array_values($categoryTotals), 0, 12)),
            'data_category_count' => array_map(fn (array $row) => $row['cantidad'], array_slice(array_values($categoryTotals), 0, 12)),
            'labels_product' => array_slice(array_keys($productTotals), 0, 15),
            'data_product_total' => array_map(fn (array $row) => round($row['total'], 2), array_slice(array_values($productTotals), 0, 15)),
            'data_product_count' => array_map(fn (array $row) => $row['cantidad'], array_slice(array_values($productTotals), 0, 15)),
        ];
    }

    protected function resolveBalancesRange(?string $from, ?string $to): array
    {
        if ($from || $to) {
            $dateFrom = $from
                ? CarbonImmutable::parse($from)->startOfDay()
                : CarbonImmutable::parse((string) $to)->startOfDay();
            $dateTo = $to
                ? CarbonImmutable::parse($to)->endOfDay()
                : CarbonImmutable::parse((string) $from)->endOfDay();

            if ($dateFrom->greaterThan($dateTo)) {
                [$dateFrom, $dateTo] = [$dateTo->startOfDay(), $dateFrom->endOfDay()];
            }

            return [
                'from' => $dateFrom,
                'to' => $dateTo,
                'defaulted_to_latest_available' => false,
                'latest_available_date' => null,
            ];
        }

        $today = CarbonImmutable::today();
        $latestConfirmedSaleAt = Venta::query()
            ->where('estado', Venta::ESTADO_CONFIRMADA)
            ->orderByDesc('fecha')
            ->value('fecha');

        if (! $latestConfirmedSaleAt) {
            return [
                'from' => $today->startOfDay(),
                'to' => $today->endOfDay(),
                'defaulted_to_latest_available' => false,
                'latest_available_date' => null,
            ];
        }

        $latestAvailableDate = CarbonImmutable::parse($latestConfirmedSaleAt);

        return [
            'from' => $latestAvailableDate->startOfDay(),
            'to' => $latestAvailableDate->endOfDay(),
            'defaulted_to_latest_available' => ! $latestAvailableDate->isSameDay($today),
            'latest_available_date' => $latestAvailableDate,
        ];
    }

    public function buildVentaItemName(VentaItem $item): string
    {
        $variante = $item->variante;
        $producto = $variante?->producto;
        $parts = [trim((string) $producto?->nombre)];

        foreach ($variante?->atributos ?? [] as $atributo) {
            $attributeName = strtolower(trim((string) $atributo->atributo?->nombre));
            $value = trim((string) $atributo->valor?->valor);

            if ($value === '' || ! in_array($attributeName, ['color', 'talle', 'tamaño', 'tamanio', 'size'], true)) {
                continue;
            }

            $parts[] = $value;
        }

        $parts = array_values(array_filter($parts));

        return $parts !== [] ? implode(' - ', $parts) : ((string) $variante?->sku ?: 'Item');
    }
}
