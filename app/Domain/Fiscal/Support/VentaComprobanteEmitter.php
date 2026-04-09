<?php

namespace App\Domain\Fiscal\Support;

use App\Domain\Fiscal\Contracts\InvoiceAuthorizer;
use App\Domain\Fiscal\Models\VentaComprobante;
use App\Domain\Fiscal\Models\VentaComprobanteEvento;
use App\Domain\Ventas\Models\Venta;
use App\Models\User;
use DomainException;
use Throwable;
use Illuminate\Support\Facades\DB;

class VentaComprobanteEmitter
{
    public function __construct(
        protected FiscalConfigManager $configManager,
        protected FiscalDocumentBuilder $documentBuilder,
        protected InvoiceAuthorizer $invoiceAuthorizer,
    ) {
    }

    public function registerSaleOutcome(User $user, Venta $sale, array $payload = []): ?VentaComprobante
    {
        $sale->loadMissing(['sucursal', 'items']);

        $branch = $sale->sucursal;

        if (! $branch) {
            throw new DomainException('La venta no tiene sucursal asociada para registrar estado fiscal.');
        }

        $action = $this->configManager->resolveAction($branch, (string) ($payload['accion_fiscal'] ?? ''));
        $ui = $this->configManager->assertActionAllowed($branch, $action);

        return match ($action) {
            Venta::ACCION_FISCAL_FACTURA_EXTERNA_REFERENCIADA => $this->registerExternalReference($user, $sale, $ui, $payload),
            Venta::ACCION_FISCAL_FACTURA_ELECTRONICA => $this->registerElectronicDocument($user, $sale, $ui, $payload),
            default => $this->registerOnlyInternalSale($sale),
        };
    }

    public function retryElectronicDocument(User $user, VentaComprobante $document): VentaComprobante
    {
        return DB::transaction(function () use ($user, $document): VentaComprobante {
            $document = VentaComprobante::query()
                ->with(['venta.sucursal'])
                ->lockForUpdate()
                ->findOrFail($document->id);

            $sale = Venta::query()
                ->with('sucursal')
                ->lockForUpdate()
                ->find($document->venta_id);
            $branch = $sale?->sucursal;

            if (! $sale || ! $branch) {
                throw new DomainException('El comprobante no tiene una venta o sucursal válida para reintentar la emisión.');
            }

            if ($document->modo_emision !== VentaComprobante::MODO_ELECTRONICA_ARCA) {
                throw new DomainException('Solo se pueden reintentar comprobantes electrónicos ARCA.');
            }

            if ($sale->accion_fiscal !== Venta::ACCION_FISCAL_FACTURA_ELECTRONICA) {
                throw new DomainException('La venta asociada no está configurada para factura electrónica.');
            }

            if ($document->estado === VentaComprobante::ESTADO_AUTORIZADO) {
                return $document->load(['venta.comprobantePrincipal', 'eventos']);
            }

            $ui = $this->configManager->assertActionAllowed($branch, Venta::ACCION_FISCAL_FACTURA_ELECTRONICA);
            $payload = $this->documentBuilder->retryPayloadFromDocument($document);
            $context = $this->documentBuilder->buildElectronicInvoiceContext($sale, $ui, $payload);

            $document->fill([
                ...$context['document'],
                'estado' => VentaComprobante::ESTADO_PENDIENTE,
                'numero_comprobante' => null,
                'cae' => null,
                'cae_vto' => null,
                'qr_payload_json' => null,
                'qr_url' => null,
                'resultado_arca' => null,
                'observaciones_arca_json' => null,
                'response_payload_json' => null,
                'emitido_en' => null,
            ]);
            $document->save();

            $sale->estado_fiscal = Venta::ESTADO_FISCAL_PENDIENTE;
            $sale->tiene_comprobante_fiscal = false;
            $sale->venta_comprobante_principal_id = $document->id;
            $sale->save();

            $this->registerEvent(
                $document,
                VentaComprobanteEvento::TIPO_PENDIENTE_EMISION,
                'Se reintentó la emisión electrónica del comprobante.',
                [
                    'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
                    'entorno' => $context['environment'],
                    'punto_venta' => data_get($context, 'document.punto_venta'),
                    'clase' => data_get($context, 'document.clase'),
                    'reintento' => true,
                ],
                $user,
            );

            return $this->authorizeElectronicDocument($user, $sale, $document, $context);
        });
    }

    protected function registerOnlyInternalSale(Venta $sale): ?VentaComprobante
    {
        $sale->accion_fiscal = Venta::ACCION_FISCAL_SOLO_REGISTRO;
        $sale->estado_fiscal = Venta::ESTADO_FISCAL_NO_REQUERIDO;
        $sale->tiene_comprobante_fiscal = false;
        $sale->venta_comprobante_principal_id = null;
        $sale->save();

        return null;
    }

    protected function registerExternalReference(User $user, Venta $sale, array $ui, array $payload): VentaComprobante
    {
        $reference = trim((string) ($payload['referencia_comprobante_externo'] ?? ''));

        if ($reference === '') {
            throw new DomainException('Ingresa la referencia del comprobante externo para continuar.');
        }

        $document = VentaComprobante::query()->create([
            'venta_id' => $sale->id,
            'sucursal_id' => $sale->sucursal_id,
            'modo_emision' => VentaComprobante::MODO_EXTERNA_REFERENCIADA,
            'estado' => VentaComprobante::ESTADO_REFERENCIADO,
            'tipo_comprobante' => VentaComprobante::TIPO_FACTURA,
            'clase' => $ui['default_class'],
            'fecha_emision' => $sale->fecha ?? now(),
            'punto_venta' => $ui['punto_venta'],
            'importe_neto' => $sale->fiscal_items_sin_impuestos_nacionales ?? '0.00',
            'importe_iva' => $sale->fiscal_items_iva_contenido ?? '0.00',
            'importe_otros_tributos' => $sale->fiscal_items_otros_impuestos_nacionales_indirectos ?? '0.00',
            'importe_total' => $sale->total ?? '0.00',
            'referencia_externa_tipo' => 'MANUAL',
            'referencia_externa_numero' => $reference,
            'emitido_en' => now(),
        ]);

        $this->registerEvent(
            $document,
            VentaComprobanteEvento::TIPO_REFERENCIA_EXTERNA_GUARDADA,
            'Se registró una referencia de comprobante externo.',
            [
                'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_EXTERNA_REFERENCIADA,
                'referencia' => $reference,
            ],
            $user,
        );

        $sale->accion_fiscal = Venta::ACCION_FISCAL_FACTURA_EXTERNA_REFERENCIADA;
        $sale->estado_fiscal = Venta::ESTADO_FISCAL_EXTERNO_REFERENCIADO;
        $sale->tiene_comprobante_fiscal = true;
        $sale->venta_comprobante_principal_id = $document->id;
        $sale->save();

        return $document;
    }

    protected function registerElectronicDocument(User $user, Venta $sale, array $ui, array $payload): VentaComprobante
    {
        $context = $this->documentBuilder->buildElectronicInvoiceContext($sale, $ui, $payload);

        $document = VentaComprobante::query()->create([
            'venta_id' => $sale->id,
            'sucursal_id' => $sale->sucursal_id,
            ...$context['document'],
        ]);

        $sale->accion_fiscal = Venta::ACCION_FISCAL_FACTURA_ELECTRONICA;
        $sale->estado_fiscal = Venta::ESTADO_FISCAL_PENDIENTE;
        $sale->tiene_comprobante_fiscal = false;
        $sale->venta_comprobante_principal_id = $document->id;
        $sale->save();

        $this->registerEvent(
            $document,
            VentaComprobanteEvento::TIPO_PENDIENTE_EMISION,
            'Se creó la intención de emisión electrónica y se inició el pedido de autorización.',
            [
                'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
                'entorno' => $context['environment'],
                'punto_venta' => data_get($context, 'document.punto_venta'),
                'clase' => data_get($context, 'document.clase'),
            ],
            $user,
        );

        return $this->authorizeElectronicDocument($user, $sale, $document, $context);
    }

    protected function releaseObsoleteFiscalNumberReservations(VentaComprobante $document): void
    {
        $pointOfSale = (int) ($document->punto_venta ?? 0);
        $receiptCode = (int) ($document->codigo_arca ?? 0);
        $number = $document->numero_comprobante !== null ? (int) $document->numero_comprobante : 0;

        if ($pointOfSale <= 0 || $receiptCode <= 0 || $number <= 0) {
            return;
        }

        VentaComprobante::query()
            ->whereKeyNot($document->id)
            ->where('punto_venta', $pointOfSale)
            ->where('codigo_arca', $receiptCode)
            ->where('numero_comprobante', $number)
            ->whereIn('estado', [
                VentaComprobante::ESTADO_RECHAZADO,
                VentaComprobante::ESTADO_BORRADOR,
                VentaComprobante::ESTADO_PENDIENTE,
            ])
            ->update([
                'numero_comprobante' => null,
                'updated_at' => now(),
            ]);
    }

    protected function registerEvent(
        VentaComprobante $document,
        string $type,
        string $description,
        array $payload,
        User $user,
    ): void {
        VentaComprobanteEvento::query()->create([
            'venta_comprobante_id' => $document->id,
            'tipo_evento' => $type,
            'descripcion' => $description,
            'payload_json' => $payload,
            'created_by' => $user->id,
        ]);
    }

    protected function authorizeElectronicDocument(
        User $user,
        Venta $sale,
        VentaComprobante $document,
        array $context,
    ): VentaComprobante {
        try {
            $result = $this->invoiceAuthorizer->authorize($context);

            $document->fill($result->documentAttributes);
            $this->releaseObsoleteFiscalNumberReservations($document);
            $document->save();

            $this->registerEvent(
                $document,
                $result->eventType,
                $result->eventDescription,
                $result->eventPayload,
                $user,
            );

            $sale->estado_fiscal = $result->saleFiscalState;
            $sale->tiene_comprobante_fiscal = $result->saleHasFiscalDocument;
            $sale->venta_comprobante_principal_id = $document->id;
            $sale->save();
        } catch (DomainException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $document->estado = VentaComprobante::ESTADO_PENDIENTE;
            $document->numero_comprobante = null;
            $document->cae = null;
            $document->cae_vto = null;
            $document->qr_payload_json = null;
            $document->qr_url = null;
            $document->resultado_arca = null;
            $document->observaciones_arca_json = null;
            $document->emitido_en = null;
            $document->response_payload_json = [
                'runtime_error' => $exception->getMessage(),
            ];
            $document->save();

            $sale->estado_fiscal = Venta::ESTADO_FISCAL_PENDIENTE;
            $sale->tiene_comprobante_fiscal = false;
            $sale->venta_comprobante_principal_id = $document->id;
            $sale->save();

            $this->registerEvent(
                $document,
                VentaComprobanteEvento::TIPO_ERROR_EMISION,
                'La venta quedó pendiente de reproceso por un error al comunicarse con ARCA.',
                [
                    'message' => $exception->getMessage(),
                ],
                $user,
            );
        }

        return $document->load(['venta.comprobantePrincipal', 'eventos']);
    }
}
