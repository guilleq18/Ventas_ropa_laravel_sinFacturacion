<?php

namespace App\Domain\Fiscal\Support;

use App\Domain\Fiscal\Contracts\InvoiceAuthorizer;
use App\Domain\Fiscal\Data\FiscalAuthorizationResult;
use App\Domain\Fiscal\Models\VentaComprobante;

class FakeInvoiceAuthorizer implements InvoiceAuthorizer
{
    public function __construct(
        protected FiscalQrBuilder $qrBuilder,
    ) {
    }

    public function authorize(array $context): FiscalAuthorizationResult
    {
        $pointOfSale = (int) data_get($context, 'wsfe.point_of_sale', 1);
        $receiptType = (int) data_get($context, 'wsfe.receipt_type', 11);
        $number = 1;
        $cae = '99990000123456';
        $expiresAt = now()->addDays(10)->format('Y-m-d');
        $qr = $this->qrBuilder->build([
            'ver' => 1,
            'fecha' => data_get($context, 'document.fecha_emision')?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'cuit' => (int) data_get($context, 'wsfe.auth.cuit', 20000000001),
            'ptoVta' => $pointOfSale,
            'tipoCmp' => $receiptType,
            'nroCmp' => $number,
            'importe' => (float) data_get($context, 'document.importe_total', 0),
            'moneda' => 'PES',
            'ctz' => 1,
            'tipoDocRec' => (int) data_get($context, 'document.doc_tipo_receptor', 99),
            'nroDocRec' => (int) data_get($context, 'receiver.doc_number_numeric', 0),
            'tipoCodAut' => 'E',
            'codAut' => $cae,
        ]);

        return new FiscalAuthorizationResult(
            documentState: VentaComprobante::ESTADO_AUTORIZADO,
            saleFiscalState: 'AUTORIZADO',
            saleHasFiscalDocument: true,
            eventType: 'AUTORIZADO_ARCA',
            eventDescription: 'El comprobante electrónico quedó autorizado con el gateway fiscal fake.',
            eventPayload: [
                'gateway' => 'fake',
                'cae' => $cae,
                'numero_comprobante' => $number,
            ],
            documentAttributes: [
                ...data_get($context, 'document', []),
                'estado' => VentaComprobante::ESTADO_AUTORIZADO,
                'numero_comprobante' => $number,
                'cae' => $cae,
                'cae_vto' => $expiresAt,
                'qr_payload_json' => $qr['payload'],
                'qr_url' => $qr['url'],
                'resultado_arca' => 'A',
                'observaciones_arca_json' => [
                    'observations' => [],
                    'errors' => [],
                    'events' => [
                        [
                            'code' => 0,
                            'message' => 'Autorización fake para testing/local.',
                        ],
                    ],
                ],
                'request_payload_json' => [
                    ...data_get($context, 'document.request_payload_json', []),
                    'gateway' => 'fake',
                    'wsfe_request' => data_get($context, 'wsfe', []),
                ],
                'response_payload_json' => [
                    'gateway' => 'fake',
                    'cab_resultado' => 'A',
                    'detalle_resultado' => 'A',
                    'cbte_desde' => $number,
                    'cbte_hasta' => $number,
                    'cae' => $cae,
                    'cae_vto' => str_replace('-', '', $expiresAt),
                    'observations' => [],
                    'errors' => [],
                    'events' => [],
                ],
                'emitido_en' => now(),
            ],
        );
    }
}
