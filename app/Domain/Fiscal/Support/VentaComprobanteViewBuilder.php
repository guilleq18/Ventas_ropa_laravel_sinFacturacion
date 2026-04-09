<?php

namespace App\Domain\Fiscal\Support;

use App\Domain\Admin\Support\AdminReportService;
use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Fiscal\Models\VentaComprobante;
use App\Domain\Ventas\Models\VentaItem;
use App\Domain\Ventas\Models\VentaPago;
use App\Support\Fiscal\FiscalMath;

class VentaComprobanteViewBuilder
{
    public function __construct(
        protected AdminSettingsManager $settingsManager,
        protected AdminReportService $reportService,
    ) {
    }

    public function build(VentaComprobante $document): array
    {
        $document->loadMissing([
            'venta.sucursal.fiscalConfig',
            'venta.cliente',
            'venta.cajero',
            'venta.items.variante.producto',
            'venta.items.variante.atributos.atributo',
            'venta.items.variante.atributos.valor',
            'venta.pagos.plan',
        ]);

        $sale = $document->venta;
        $items = $sale?->items?->map(function (VentaItem $item): VentaItem {
            $item->nombre_fiscal = $this->reportService->buildVentaItemName($item);

            return $item;
        }) ?? collect();
        $payments = $sale?->pagos?->map(function (VentaPago $payment): VentaPago {
            $payment->tipo_fiscal = $this->paymentLabel($payment->tipo);
            $payment->recargo_fiscal = (float) ($payment->recargo_monto ?? 0);
            $payment->total_fiscal = (float) $payment->monto + (float) ($payment->recargo_monto ?? 0);

            return $payment;
        }) ?? collect();
        $company = $this->companyData($sale);

        return [
            'documento' => $document,
            'venta' => $sale,
            'empresa' => $company,
            'domicilioFiscalEmision' => trim((string) ($sale?->sucursal?->fiscalConfig?->domicilio_fiscal_emision ?? '')) ?: null,
            'items' => $items,
            'pagos' => $payments,
            'totalItems' => (float) $items->sum(fn (VentaItem $item) => (float) $item->subtotal),
            'totalPagado' => (float) $payments->sum(fn (VentaPago $payment) => (float) $payment->total_fiscal),
            'fiscalInformativo' => $this->informativeFiscalBreakdown($sale),
        ];
    }

    protected function companyData($sale): array
    {
        $current = $this->settingsManager->getCompanyData();
        $condition = $this->settingsManager->normalizeFiscalCondition((string) (
            $sale?->empresa_condicion_fiscal_snapshot !== ''
                ? $sale?->empresa_condicion_fiscal_snapshot
                : ($current['condicion_fiscal'] ?? '')
        ));

        return [
            'nombre' => (string) ($sale?->empresa_nombre_snapshot !== '' ? $sale?->empresa_nombre_snapshot : ($current['nombre'] ?? '')),
            'razon_social' => (string) ($sale?->empresa_razon_social_snapshot !== '' ? $sale?->empresa_razon_social_snapshot : ($current['razon_social'] ?? '')),
            'cuit' => (string) ($sale?->empresa_cuit_snapshot !== '' ? $sale?->empresa_cuit_snapshot : ($current['cuit'] ?? '')),
            'direccion' => (string) ($sale?->empresa_direccion_snapshot !== '' ? $sale?->empresa_direccion_snapshot : ($current['direccion'] ?? '')),
            'condicion_fiscal_label' => AdminSettingsManager::FISCAL_CHOICES[$condition] ?? 'Monotributista',
        ];
    }

    protected function paymentLabel(?string $type): string
    {
        return match ((string) $type) {
            'CONTADO' => 'Contado',
            'DEBITO' => 'Débito',
            'CREDITO' => 'Crédito',
            'TRANSFERENCIA' => 'Transferencia',
            'QR' => 'QR',
            'CUENTA_CORRIENTE' => 'Cuenta corriente',
            default => (string) ($type ?: 'Sin medio'),
        };
    }

    protected function informativeFiscalBreakdown($sale): array
    {
        if (! $sale) {
            return FiscalMath::desglosarMontoFinalGravadoConIva('0.00');
        }

        if ($sale->fiscal_items_sin_impuestos_nacionales !== null && $sale->fiscal_items_iva_contenido !== null) {
            return [
                'monto_final' => FiscalMath::money($sale->total ?? '0'),
                'monto_sin_impuestos_nacionales' => FiscalMath::money($sale->fiscal_items_sin_impuestos_nacionales),
                'iva_contenido' => FiscalMath::money($sale->fiscal_items_iva_contenido),
                'iva_alicuota_pct' => FiscalMath::money(FiscalMath::IVA_GENERAL_PCT),
                'otros_impuestos_nacionales_indirectos' => FiscalMath::money(
                    $sale->fiscal_items_otros_impuestos_nacionales_indirectos ?? '0',
                ),
            ];
        }

        return FiscalMath::desglosarMontoFinalGravadoConIva($sale->total ?? '0.00');
    }
}
