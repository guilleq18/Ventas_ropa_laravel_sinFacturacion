<?php

namespace App\Domain\Fiscal\Support;

use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Fiscal\Models\VentaComprobante;
use App\Domain\Ventas\Models\Venta;
use App\Support\Fiscal\FiscalMath;
use DomainException;

class FiscalDocumentBuilder
{
    public const array DOC_TYPE_OPTIONS = [
        'CONSUMIDOR_FINAL' => [
            'label' => 'Consumidor final',
            'code' => 99,
            'requires_number' => false,
        ],
        'DNI' => [
            'label' => 'DNI',
            'code' => 96,
            'requires_number' => true,
        ],
        'CUIT' => [
            'label' => 'CUIT',
            'code' => 80,
            'requires_number' => true,
        ],
        'CUIL' => [
            'label' => 'CUIL',
            'code' => 86,
            'requires_number' => true,
        ],
    ];

    public const array RECEIVER_VAT_CONDITION_OPTIONS = [
        'CONSUMIDOR_FINAL' => [
            'label' => 'Consumidor Final',
            'by_class' => ['B' => 5, 'C' => 5],
        ],
        'IVA_RESPONSABLE_INSCRIPTO' => [
            'label' => 'IVA Responsable Inscripto',
            'by_class' => ['C' => 1],
        ],
        'RESPONSABLE_MONOTRIBUTO' => [
            'label' => 'Responsable Monotributo',
            'by_class' => ['C' => 6],
        ],
        'MONOTRIBUTISTA_SOCIAL' => [
            'label' => 'Monotributista Social',
            'by_class' => ['C' => 13],
        ],
        'MONOTRIBUTO_TRABAJADOR_INDEPENDIENTE_PROMOVIDO' => [
            'label' => 'Monotributo Trabajador Independiente Promovido',
            'by_class' => ['C' => 16],
        ],
        'IVA_SUJETO_EXENTO' => [
            'label' => 'IVA Sujeto Exento',
            'by_class' => ['B' => 4, 'C' => 4],
        ],
        'SUJETO_NO_CATEGORIZADO' => [
            'label' => 'Sujeto No Categorizado',
            'by_class' => ['B' => 7, 'C' => 7],
        ],
        'PROVEEDOR_DEL_EXTERIOR' => [
            'label' => 'Proveedor del Exterior',
            'by_class' => ['B' => 8, 'C' => 8],
        ],
        'CLIENTE_DEL_EXTERIOR' => [
            'label' => 'Cliente del Exterior',
            'by_class' => ['B' => 9, 'C' => 9],
        ],
        'IVA_LIBERADO_LEY_19640' => [
            'label' => 'IVA Liberado - Ley 19.640',
            'by_class' => ['B' => 10, 'C' => 10],
        ],
        'IVA_NO_ALCANZADO' => [
            'label' => 'IVA No Alcanzado',
            'by_class' => ['B' => 15, 'C' => 15],
        ],
    ];

    public function __construct(
        protected AdminSettingsManager $settingsManager,
        protected ArcaCredentialManager $arcaCredentialManager,
    ) {
    }

    public function electronicReceiverOptions(): array
    {
        return self::DOC_TYPE_OPTIONS;
    }

    public function electronicReceiverVatConditionOptions(?string $class = null): array
    {
        $normalizedClass = $this->normalizeInvoiceClass($class);
        $rows = [];

        foreach (self::RECEIVER_VAT_CONDITION_OPTIONS as $key => $meta) {
            $conditionId = $meta['by_class'][$normalizedClass] ?? null;

            if ($conditionId === null) {
                continue;
            }

            $rows[$key] = [
                'label' => $meta['label'],
                'id' => $conditionId,
            ];
        }

        return $rows;
    }

    public function defaultReceiverDraft(?string $class = null): array
    {
        $normalizedClass = $this->normalizeInvoiceClass($class);
        $vatConditions = $this->electronicReceiverVatConditionOptions($normalizedClass);
        $defaultVatKey = array_key_exists('CONSUMIDOR_FINAL', $vatConditions)
            ? 'CONSUMIDOR_FINAL'
            : array_key_first($vatConditions);

        return [
            'fiscal_receptor_doc_tipo' => 'CONSUMIDOR_FINAL',
            'fiscal_receptor_doc_nro' => '',
            'fiscal_receptor_nombre' => 'Consumidor Final',
            'fiscal_receptor_domicilio' => '',
            'fiscal_receptor_condicion_iva' => $defaultVatKey ?: 'CONSUMIDOR_FINAL',
        ];
    }

    public function buildElectronicInvoiceContext(Venta $sale, array $ui, array $payload = []): array
    {
        $sale->loadMissing(['sucursal', 'cliente', 'items.variante.producto']);

        $class = $this->invoiceClassForSale($sale);
        $receiptCode = $this->receiptCodeForClass($class);
        $receiver = $this->resolveReceiver($sale, $ui, $payload, $class);
        $amounts = $this->buildAmounts($sale, $class);
        $representedCuit = $this->representedCuit($sale);
        $issueDate = ($sale->fecha ?? now())->copy();
        $detail = [
            'Concepto' => 1,
            'DocTipo' => $receiver['doc_type_code'],
            'DocNro' => $receiver['doc_number_numeric'],
            'CbteFch' => $issueDate->format('Ymd'),
            'ImpTotal' => $amounts['importe_total'],
            'ImpTotConc' => '0.00',
            'ImpNeto' => $amounts['importe_neto'],
            'ImpOpEx' => '0.00',
            'ImpTrib' => $amounts['importe_otros_tributos'],
            'ImpIVA' => $amounts['importe_iva'],
            'MonId' => 'PES',
            'MonCotiz' => '1.000000',
            'Iva' => $amounts['iva_rows'],
            'CondicionIVAReceptorId' => $receiver['vat_condition_id'],
        ];

        return [
            'environment' => (string) ($ui['entorno'] ?? 'HOMOLOGACION'),
            'service_id' => (string) config('fiscal.arca.service_id', 'wsfe'),
            'document' => [
                'modo_emision' => VentaComprobante::MODO_ELECTRONICA_ARCA,
                'estado' => VentaComprobante::ESTADO_PENDIENTE,
                'tipo_comprobante' => VentaComprobante::TIPO_FACTURA,
                'clase' => $class,
                'codigo_arca' => $receiptCode,
                'punto_venta' => (int) ($ui['punto_venta'] ?? 0),
                'fecha_emision' => $issueDate,
                'moneda' => 'PES',
                'cotizacion_moneda' => '1.000000',
                'doc_tipo_receptor' => $receiver['doc_type_code'],
                'doc_nro_receptor' => $receiver['doc_number_display'],
                'receptor_nombre' => $receiver['name'],
                'receptor_condicion_iva' => $receiver['condition_label'],
                'receptor_domicilio' => $receiver['address'],
                'importe_neto' => $amounts['importe_neto'],
                'importe_iva' => $amounts['importe_iva'],
                'importe_otros_tributos' => $amounts['importe_otros_tributos'],
                'importe_total' => $amounts['importe_total'],
                'request_payload_json' => [
                    'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
                    'entorno' => (string) ($ui['entorno'] ?? 'HOMOLOGACION'),
                    'clase' => $class,
                    'codigo_arca' => $receiptCode,
                    'punto_venta' => (int) ($ui['punto_venta'] ?? 0),
                    'receptor' => [
                        'doc_tipo' => $receiver['doc_type_key'],
                        'doc_tipo_codigo' => $receiver['doc_type_code'],
                        'doc_nro' => $receiver['doc_number_display'],
                        'nombre' => $receiver['name'],
                        'domicilio' => $receiver['address'],
                        'condicion_iva' => $receiver['vat_condition_label'],
                        'condicion_iva_id' => $receiver['vat_condition_id'],
                    ],
                    'importe_total' => $amounts['importe_total'],
                    'importe_neto' => $amounts['importe_neto'],
                    'importe_iva' => $amounts['importe_iva'],
                    'importe_otros_tributos' => $amounts['importe_otros_tributos'],
                ],
            ],
            'wsfe' => [
                'auth' => [
                    'cuit' => $representedCuit,
                ],
                'point_of_sale' => (int) ($ui['punto_venta'] ?? 0),
                'receipt_type' => $receiptCode,
                'detail' => $detail,
            ],
            'receiver' => $receiver,
        ];
    }

    public function invoiceClassForSale(Venta $sale): string
    {
        $condition = $this->settingsManager->normalizeFiscalCondition((string) (
            $sale->empresa_condicion_fiscal_snapshot !== ''
                ? $sale->empresa_condicion_fiscal_snapshot
                : $this->settingsManager->getFiscalCondition()
        ));

        return $condition === AdminSettingsManager::FISCAL_RESPONSABLE_INSCRIPTO ? 'B' : 'C';
    }

    public function receiptCodeForClass(string $class): int
    {
        return strtoupper($class) === 'B' ? 6 : 11;
    }

    protected function representedCuit(Venta $sale): int
    {
        $configured = preg_replace('/\D+/', '', $this->arcaCredentialManager->resolvedRepresentedCuit());

        if (strlen((string) $configured) === 11) {
            return (int) $configured;
        }

        $snapshot = preg_replace('/\D+/', '', (string) ($sale->empresa_cuit_snapshot ?? ''));

        if (strlen((string) $snapshot) === 11) {
            return (int) $snapshot;
        }

        $company = $this->settingsManager->getCompanyData();
        $companyCuit = preg_replace('/\D+/', '', (string) ($company['cuit'] ?? ''));

        if (strlen((string) $companyCuit) === 11) {
            return (int) $companyCuit;
        }

        throw new DomainException('No hay CUIT válido configurado para emitir factura electrónica.');
    }

    protected function resolveReceiver(Venta $sale, array $ui, array $payload, string $invoiceClass): array
    {
        $client = $sale->cliente;
        $threshold = (float) config('fiscal.consumer_final_identification_threshold', 10000000);
        $total = (float) ($sale->total ?? 0);
        $requiresIdentification = (bool) ($ui['requiere_receptor_en_todas'] ?? false) || $total >= $threshold;

        $docTypeKey = $this->normalizeDocTypeKey((string) ($payload['fiscal_receptor_doc_tipo'] ?? ''));
        $docNumber = preg_replace('/\D+/', '', (string) ($payload['fiscal_receptor_doc_nro'] ?? '')) ?: '';
        $name = trim((string) ($payload['fiscal_receptor_nombre'] ?? ''));
        $address = trim((string) ($payload['fiscal_receptor_domicilio'] ?? ''));
        $vatConditionKey = $this->normalizeReceiverVatConditionKey((string) ($payload['fiscal_receptor_condicion_iva'] ?? ''));

        if ($docTypeKey === 'CONSUMIDOR_FINAL' && $client && $client->dni) {
            $docTypeKey = 'DNI';
        }

        if ($docNumber === '' && $client?->dni) {
            $docNumber = preg_replace('/\D+/', '', (string) $client->dni) ?: '';
        }

        if ($name === '' && $client) {
            $name = $client->nombre_completo;
        }

        if ($address === '' && $client?->direccion) {
            $address = trim((string) $client->direccion);
        }

        if ($name === '') {
            $name = 'Consumidor Final';
        }

        if ($docTypeKey === 'CONSUMIDOR_FINAL' && $docNumber !== '') {
            $docTypeKey = strlen($docNumber) === 11 ? 'CUIT' : 'DNI';
        }

        if ($requiresIdentification && $docTypeKey === 'CONSUMIDOR_FINAL') {
            throw new DomainException(
                'Esta factura electrónica requiere identificar al receptor. Completa documento, nombre y domicilio.',
            );
        }

        if ($requiresIdentification && $docNumber === '') {
            throw new DomainException('Ingresa el número de documento del receptor para emitir la factura electrónica.');
        }

        if ($requiresIdentification && trim($name) === '') {
            throw new DomainException('Ingresa el nombre o razón social del receptor para emitir la factura electrónica.');
        }

        if ($requiresIdentification && trim($address) === '') {
            throw new DomainException('Ingresa el domicilio del receptor para emitir la factura electrónica.');
        }

        $docType = self::DOC_TYPE_OPTIONS[$docTypeKey] ?? self::DOC_TYPE_OPTIONS['CONSUMIDOR_FINAL'];
        $vatCondition = $this->resolveVatCondition($invoiceClass, $vatConditionKey);
        $numberNumeric = $docTypeKey === 'CONSUMIDOR_FINAL' ? 0 : (int) $docNumber;

        if (($docType['requires_number'] ?? false) && $numberNumeric <= 0) {
            throw new DomainException('El documento del receptor debe ser numérico y mayor a cero.');
        }

        return [
            'doc_type_key' => $docTypeKey,
            'doc_type_label' => $docType['label'],
            'doc_type_code' => (int) $docType['code'],
            'doc_number_numeric' => $numberNumeric,
            'doc_number_display' => $docTypeKey === 'CONSUMIDOR_FINAL' ? '0' : $docNumber,
            'name' => $name,
            'address' => $address !== '' ? $address : null,
            'condition_label' => $vatCondition['label'],
            'vat_condition_key' => $vatConditionKey,
            'vat_condition_label' => $vatCondition['label'],
            'vat_condition_id' => $vatCondition['id'],
        ];
    }

    protected function buildAmounts(Venta $sale, string $class): array
    {
        $total = FiscalMath::money($sale->total ?? '0');

        if (strtoupper($class) === 'C') {
            return [
                'importe_total' => $total,
                'importe_neto' => $total,
                'importe_iva' => '0.00',
                'importe_otros_tributos' => '0.00',
                'iva_rows' => [],
            ];
        }

        $net = FiscalMath::money($sale->fiscal_items_sin_impuestos_nacionales ?? '0');
        $iva = FiscalMath::money($sale->fiscal_items_iva_contenido ?? '0');
        $other = FiscalMath::money($sale->fiscal_items_otros_impuestos_nacionales_indirectos ?? '0');
        $ivaRows = (float) $iva > 0 ? [[
            'Id' => 5,
            'BaseImp' => $net,
            'Importe' => $iva,
        ]] : [];

        return [
            'importe_total' => $total,
            'importe_neto' => $net,
            'importe_iva' => $iva,
            'importe_otros_tributos' => $other,
            'iva_rows' => $ivaRows,
        ];
    }

    protected function normalizeDocTypeKey(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return match ($normalized) {
            'DNI', '96' => 'DNI',
            'CUIT', '80' => 'CUIT',
            'CUIL', '86' => 'CUIL',
            default => 'CONSUMIDOR_FINAL',
        };
    }

    protected function normalizeReceiverVatConditionKey(?string $value): string
    {
        $normalized = strtoupper(str_replace([' ', '-', '°', '.', '_'], '', trim((string) $value)));

        return match ($normalized) {
            'IVARESPONSABLEINSCRIPTO', 'RI' => 'IVA_RESPONSABLE_INSCRIPTO',
            'RESPONSABLEMONOTRIBUTO', 'MONOTRIBUTO', 'MONOTRIBUTISTA' => 'RESPONSABLE_MONOTRIBUTO',
            'MONOTRIBUTISTASOCIAL' => 'MONOTRIBUTISTA_SOCIAL',
            'MONOTRIBUTOTRABAJADORINDEPENDIENTEPROMOVIDO', 'TRABAJADORINDEPENDIENTEPROMOVIDO' => 'MONOTRIBUTO_TRABAJADOR_INDEPENDIENTE_PROMOVIDO',
            'IVASUJETOEXENTO', 'EXENTO' => 'IVA_SUJETO_EXENTO',
            'SUJETONOCATEGORIZADO' => 'SUJETO_NO_CATEGORIZADO',
            'PROVEEDORDELEXTERIOR' => 'PROVEEDOR_DEL_EXTERIOR',
            'CLIENTEDELEXTERIOR' => 'CLIENTE_DEL_EXTERIOR',
            'IVALIBERADOLEY19640', 'LEY19640' => 'IVA_LIBERADO_LEY_19640',
            'IVANOALCANZADO', 'NOALCANZADO' => 'IVA_NO_ALCANZADO',
            default => 'CONSUMIDOR_FINAL',
        };
    }

    protected function resolveVatCondition(string $invoiceClass, string $conditionKey): array
    {
        $normalizedClass = $this->normalizeInvoiceClass($invoiceClass);
        $meta = self::RECEIVER_VAT_CONDITION_OPTIONS[$conditionKey] ?? self::RECEIVER_VAT_CONDITION_OPTIONS['CONSUMIDOR_FINAL'];
        $conditionId = $meta['by_class'][$normalizedClass] ?? null;

        if ($conditionId === null) {
            throw new DomainException(
                "La condición IVA del receptor seleccionada no es válida para una factura {$normalizedClass}.",
            );
        }

        return [
            'label' => $meta['label'],
            'id' => $conditionId,
        ];
    }

    protected function normalizeInvoiceClass(?string $class): string
    {
        return strtoupper(trim((string) $class)) === 'B' ? 'B' : 'C';
    }
}
