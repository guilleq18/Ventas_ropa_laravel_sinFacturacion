<?php

namespace App\Domain\Fiscal\Support;

use App\Domain\Fiscal\Contracts\InvoiceAuthorizer;
use App\Domain\Fiscal\Data\FiscalAuthorizationResult;
use App\Domain\Fiscal\Models\VentaComprobante;

class ArcaInvoiceAuthorizer implements InvoiceAuthorizer
{
    public function __construct(
        protected ArcaWsaaClient $wsaaClient,
        protected ArcaWsfeClient $wsfeClient,
        protected FiscalQrBuilder $qrBuilder,
    ) {
    }

    public function authorize(array $context): FiscalAuthorizationResult
    {
        $environment = (string) ($context['environment'] ?? 'HOMOLOGACION');
        $serviceId = (string) ($context['service_id'] ?? 'wsfe');
        $auth = $this->wsaaClient->accessTicket($environment, $serviceId);
        $auth['cuit'] = (int) data_get($context, 'wsfe.auth.cuit');
        $pointOfSale = (int) data_get($context, 'wsfe.point_of_sale');
        $receiptType = (int) data_get($context, 'wsfe.receipt_type');
        $detail = (array) data_get($context, 'wsfe.detail', []);
        $last = $this->wsfeClient->getLastAuthorized($environment, $auth, $pointOfSale, $receiptType);
        $nextNumber = max((int) ($last['numero'] ?? 0), 0) + 1;

        $detail['CbteDesde'] = $nextNumber;
        $detail['CbteHasta'] = $nextNumber;

        $requestPayload = [
            ...data_get($context, 'document.request_payload_json', []),
            'auth_cuit' => $auth['cuit'],
            'wsaa_expiration_time' => (string) ($auth['expiration_time'] ?? ''),
            'ultimo_comprobante_autorizado' => (int) ($last['numero'] ?? 0),
            'wsfe_request' => [
                'point_of_sale' => $pointOfSale,
                'receipt_type' => $receiptType,
                'detail' => $detail,
            ],
        ];

        $response = $this->wsfeClient->requestCae($environment, $auth, [
            'point_of_sale' => $pointOfSale,
            'receipt_type' => $receiptType,
            'detail' => $detail,
        ]);

        $commonAttributes = [
            ...data_get($context, 'document', []),
            'numero_comprobante' => $nextNumber,
            'request_payload_json' => $requestPayload,
            'response_payload_json' => $response,
            'resultado_arca' => (string) ($response['detalle_resultado'] ?: $response['cab_resultado']),
            'observaciones_arca_json' => [
                'observations' => $response['observations'],
                'errors' => $response['errors'],
                'events' => $response['events'],
            ],
            'emitido_en' => now(),
        ];

        if (($response['detalle_resultado'] ?? '') === 'A' && (string) ($response['cae'] ?? '') !== '') {
            $qr = $this->qrBuilder->build([
                'ver' => 1,
                'fecha' => data_get($context, 'document.fecha_emision')?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'cuit' => $auth['cuit'],
                'ptoVta' => $pointOfSale,
                'tipoCmp' => $receiptType,
                'nroCmp' => $nextNumber,
                'importe' => (float) data_get($context, 'document.importe_total', 0),
                'moneda' => data_get($context, 'document.moneda', 'PES'),
                'ctz' => (float) data_get($context, 'document.cotizacion_moneda', 1),
                'tipoDocRec' => (int) data_get($context, 'document.doc_tipo_receptor', 99),
                'nroDocRec' => (int) data_get($context, 'receiver.doc_number_numeric', 0),
                'tipoCodAut' => 'E',
                'codAut' => (string) $response['cae'],
            ]);

            return new FiscalAuthorizationResult(
                documentState: VentaComprobante::ESTADO_AUTORIZADO,
                saleFiscalState: 'AUTORIZADO',
                saleHasFiscalDocument: true,
                eventType: 'AUTORIZADO_ARCA',
                eventDescription: 'ARCA autorizó el comprobante electrónico.',
                eventPayload: [
                    'cae' => (string) $response['cae'],
                    'cae_vto' => (string) $response['cae_vto'],
                    'numero_comprobante' => $nextNumber,
                    'resultado' => (string) $response['detalle_resultado'],
                ],
                documentAttributes: [
                    ...$commonAttributes,
                    'estado' => VentaComprobante::ESTADO_AUTORIZADO,
                    'cae' => (string) $response['cae'],
                    'cae_vto' => $this->normalizeDate($response['cae_vto'] ?? ''),
                    'qr_payload_json' => $qr['payload'],
                    'qr_url' => $qr['url'],
                ],
            );
        }

        return new FiscalAuthorizationResult(
            documentState: VentaComprobante::ESTADO_RECHAZADO,
            saleFiscalState: 'RECHAZADO',
            saleHasFiscalDocument: false,
            eventType: 'RECHAZADO_ARCA',
            eventDescription: 'ARCA rechazó el comprobante electrónico.',
            eventPayload: [
                'numero_comprobante' => $nextNumber,
                'resultado' => (string) ($response['detalle_resultado'] ?: $response['cab_resultado']),
                'observations' => $response['observations'],
                'errors' => $response['errors'],
            ],
            documentAttributes: [
                ...$commonAttributes,
                'estado' => VentaComprobante::ESTADO_RECHAZADO,
                'numero_comprobante' => null,
                'cae' => null,
                'cae_vto' => null,
                'qr_payload_json' => null,
                'qr_url' => null,
            ],
        );
    }

    protected function normalizeDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{8}$/', $value) === 1) {
            return substr($value, 0, 4).'-'.substr($value, 4, 2).'-'.substr($value, 6, 2);
        }

        return $value;
    }
}
