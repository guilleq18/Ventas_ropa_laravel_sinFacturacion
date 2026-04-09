<?php

namespace App\Domain\Caja\Support;

use App\Domain\Admin\Support\AdminReportService;
use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Fiscal\Models\VentaComprobante;
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
            'comprobantePrincipal.eventos',
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
            'fiscalStatus' => $this->buildFiscalStatus($sale),
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

    protected function buildFiscalStatus(Venta $sale): ?array
    {
        $document = $sale->comprobantePrincipal;

        if (! $document) {
            return null;
        }

        $latestEvent = $document->eventos
            ->sortByDesc(fn ($event) => $event->created_at?->getTimestamp() ?? 0)
            ->first();
        $issue = $this->extractFiscalIssue($document);
        $isElectronic = $sale->accion_fiscal === Venta::ACCION_FISCAL_FACTURA_ELECTRONICA;
        $canRetry = $isElectronic && in_array($document->estado, [
            VentaComprobante::ESTADO_PENDIENTE,
            VentaComprobante::ESTADO_RECHAZADO,
        ], true);

        return [
            'document' => $document,
            'isElectronic' => $isElectronic,
            'isAuthorized' => $document->estado === VentaComprobante::ESTADO_AUTORIZADO,
            'isPending' => $document->estado === VentaComprobante::ESTADO_PENDIENTE,
            'isRejected' => $document->estado === VentaComprobante::ESTADO_RECHAZADO,
            'canRetry' => $canRetry,
            'headline' => match ($document->estado) {
                VentaComprobante::ESTADO_AUTORIZADO => 'Factura autorizada y lista para imprimir.',
                VentaComprobante::ESTADO_RECHAZADO => 'ARCA rechazó el comprobante en el último intento.',
                default => 'La emisión fiscal quedó pendiente.',
            },
            'issueMessage' => $issue['summary'],
            'technicalMessage' => $issue['technical'],
            'lastEventDescription' => $latestEvent?->descripcion,
            'lastAttemptAt' => $document->updated_at?->format('d/m/Y H:i:s'),
            'eventCount' => $document->eventos->count(),
        ];
    }

    protected function extractFiscalIssue(VentaComprobante $document): array
    {
        $runtimeError = trim((string) data_get($document->response_payload_json, 'runtime_error', ''));

        if ($runtimeError !== '') {
            return [
                'summary' => $this->summarizeFiscalRuntimeError($runtimeError),
                'technical' => $runtimeError,
            ];
        }

        $groups = [
            data_get($document->response_payload_json, 'errors', []),
            data_get($document->observaciones_arca_json, 'errors', []),
            data_get($document->response_payload_json, 'observations', []),
            data_get($document->observaciones_arca_json, 'observations', []),
            data_get($document->response_payload_json, 'events', []),
            data_get($document->observaciones_arca_json, 'events', []),
        ];

        foreach ($groups as $group) {
            foreach ((array) $group as $row) {
                $message = $this->normalizeFiscalIssueRow($row);

                if ($message !== null) {
                    return [
                        'summary' => $message,
                        'technical' => null,
                    ];
                }
            }
        }

        return [
            'summary' => null,
            'technical' => null,
        ];
    }

    protected function summarizeFiscalRuntimeError(string $message): string
    {
        $normalized = strtolower($message);

        if (str_contains($normalized, 'timed out')) {
            return 'ARCA no respondió dentro del tiempo esperado.';
        }

        if (str_contains($normalized, 'could not resolve host')) {
            return 'No se pudo resolver el servidor de ARCA.';
        }

        if (str_contains($normalized, 'failed to connect') || str_contains($normalized, 'connection refused')) {
            return 'No se pudo establecer conexión con ARCA.';
        }

        return 'No se pudo completar la emisión fiscal.';
    }

    protected function normalizeFiscalIssueRow(mixed $row): ?string
    {
        if (is_string($row)) {
            return trim($row) !== '' ? trim($row) : null;
        }

        if (! is_array($row)) {
            return null;
        }

        $code = trim((string) ($row['code'] ?? $row['Code'] ?? ''));
        $message = trim((string) ($row['message'] ?? $row['Msg'] ?? $row['msg'] ?? ''));

        if ($message === '') {
            return null;
        }

        return $code !== '' ? "{$code}: {$message}" : $message;
    }
}
