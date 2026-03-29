<?php

namespace App\Domain\Caja\Support;

use App\Domain\Admin\Support\AdminReportService;
use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Models\VentaItem;
use App\Domain\Ventas\Models\VentaPago;
use App\Support\Fiscal\FiscalMath;

class VentaTicketViewBuilder
{
    public function __construct(
        protected AdminSettingsManager $settingsManager,
        protected AdminReportService $reportService,
    ) {
    }

    public function build(Venta $sale): array
    {
        $sale->loadMissing([
            'sucursal',
            'cliente',
            'cajero',
            'items.variante.producto',
            'items.variante.atributos.atributo',
            'items.variante.atributos.valor',
            'pagos.plan',
        ]);

        $items = $sale->items->map(function (VentaItem $item): VentaItem {
            $item->nombre_ticket = $this->reportService->buildVentaItemName($item);

            return $item;
        });

        $payments = $sale->pagos->map(function (VentaPago $payment): VentaPago {
            $payment->tipo_ticket = $this->paymentLabel($payment->tipo);
            $payment->recargo_monto_ticket = (float) ($payment->recargo_monto ?? 0);
            $payment->total_pago_ticket = (float) $payment->monto + (float) ($payment->recargo_monto ?? 0);

            return $payment;
        });

        $totalItems = (float) $items->sum(fn (VentaItem $item) => (float) $item->subtotal);
        $totalRecargos = (float) $payments->sum(fn (VentaPago $payment) => (float) ($payment->recargo_monto ?? 0));
        $totalFinal = (float) ($sale->total ?: ($totalItems + $totalRecargos));
        $company = $this->buildCompanyData($sale);

        return [
            'venta' => $sale,
            'empresa' => $company,
            'items' => $items,
            'payments' => $payments,
            'cajeroNombre' => $sale->cajero?->nombre_completo ?: '-',
            'cajeroId' => $sale->cajero_id,
            'totalItems' => $totalItems,
            'totalRecargos' => $totalRecargos,
            'totalFinal' => $totalFinal,
            'fiscalItems' => $this->buildFiscalItems($sale, $totalItems),
        ];
    }

    protected function buildCompanyData(Venta $sale): array
    {
        $current = $this->settingsManager->getCompanyData();
        $conditionCode = (string) (
            $sale->empresa_condicion_fiscal_snapshot !== ''
                ? $sale->empresa_condicion_fiscal_snapshot
                : $current['condicion_fiscal']
        );
        $normalizedCondition = $this->settingsManager->normalizeFiscalCondition($conditionCode);

        return [
            'nombre' => $sale->empresa_nombre_snapshot !== '' ? $sale->empresa_nombre_snapshot : (string) $current['nombre'],
            'razon_social' => $sale->empresa_razon_social_snapshot !== '' ? $sale->empresa_razon_social_snapshot : (string) $current['razon_social'],
            'cuit' => $sale->empresa_cuit_snapshot !== '' ? $sale->empresa_cuit_snapshot : (string) $current['cuit'],
            'direccion' => $sale->empresa_direccion_snapshot !== '' ? $sale->empresa_direccion_snapshot : (string) $current['direccion'],
            'condicion_fiscal_code' => $normalizedCondition,
            'condicion_fiscal_label' => AdminSettingsManager::FISCAL_CHOICES[$normalizedCondition] ?? 'Monotributista',
            'es_responsable_inscripto' => $normalizedCondition === AdminSettingsManager::FISCAL_RESPONSABLE_INSCRIPTO,
            'es_monotributista' => $normalizedCondition === AdminSettingsManager::FISCAL_MONOTRIBUTISTA,
        ];
    }

    protected function buildFiscalItems(Venta $sale, float $totalItems): array
    {
        if ($sale->fiscal_items_sin_impuestos_nacionales !== null && $sale->fiscal_items_iva_contenido !== null) {
            return [
                'monto_final' => FiscalMath::money($totalItems),
                'monto_sin_impuestos_nacionales' => FiscalMath::money($sale->fiscal_items_sin_impuestos_nacionales),
                'iva_contenido' => FiscalMath::money($sale->fiscal_items_iva_contenido),
                'iva_alicuota_pct' => FiscalMath::money(FiscalMath::IVA_GENERAL_PCT),
                'otros_impuestos_nacionales_indirectos' => FiscalMath::money(
                    $sale->fiscal_items_otros_impuestos_nacionales_indirectos ?? '0',
                ),
            ];
        }

        return FiscalMath::desglosarMontoFinalGravadoConIva($totalItems);
    }

    protected function paymentLabel(?string $type): string
    {
        return match ((string) $type) {
            VentaPago::TIPO_CONTADO => 'Contado',
            VentaPago::TIPO_DEBITO => 'Debito',
            VentaPago::TIPO_CREDITO => 'Credito',
            VentaPago::TIPO_TRANSFERENCIA => 'Transferencia',
            VentaPago::TIPO_QR => 'QR',
            VentaPago::TIPO_CUENTA_CORRIENTE => 'Cuenta corriente',
            default => (string) ($type ?: 'Sin medio'),
        };
    }
}
