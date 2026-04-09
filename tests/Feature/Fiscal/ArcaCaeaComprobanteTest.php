<?php

namespace Tests\Feature\Fiscal;

use App\Domain\Fiscal\Models\ArcaCaeaComprobante;
use App\Domain\Fiscal\Models\ArcaCaeaPeriodo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArcaCaeaComprobanteTest extends TestCase
{
    use RefreshDatabase;

    public function test_caea_receipt_exposes_number_and_status_labels(): void
    {
        $period = ArcaCaeaPeriodo::query()->create([
            'entorno' => ArcaCaeaPeriodo::ENTORNO_HOMOLOGACION,
            'cuit_representada' => '20364362634',
            'periodo' => 202604,
            'orden' => 1,
            'caea' => '12345678901234',
        ]);

        $receipt = ArcaCaeaComprobante::query()->create([
            'arca_caea_periodo_id' => $period->id,
            'punto_venta' => 3,
            'codigo_arca' => 11,
            'numero_comprobante' => 27,
            'fecha_emision' => '2026-04-05',
            'receptor_nombre' => 'Ana Lopez',
            'doc_nro_receptor' => '30111222',
            'importe_total' => '20000.00',
            'estado_rendicion' => ArcaCaeaComprobante::ESTADO_RENDICION_INFORMADO,
            'informado_en' => now(),
        ]);

        $this->assertSame('0003-00000027', $receipt->numero_completo);
        $this->assertSame('Informado', $receipt->estado_rendicion_label);
    }

    public function test_caea_period_rendition_summary_uses_related_receipts(): void
    {
        $period = ArcaCaeaPeriodo::query()->create([
            'entorno' => ArcaCaeaPeriodo::ENTORNO_HOMOLOGACION,
            'cuit_representada' => '20364362634',
            'periodo' => 202604,
            'orden' => 1,
            'caea' => '12345678901234',
        ]);

        ArcaCaeaComprobante::query()->create([
            'arca_caea_periodo_id' => $period->id,
            'punto_venta' => 3,
            'codigo_arca' => 11,
            'numero_comprobante' => 1,
            'estado_rendicion' => ArcaCaeaComprobante::ESTADO_RENDICION_PENDIENTE,
        ]);
        ArcaCaeaComprobante::query()->create([
            'arca_caea_periodo_id' => $period->id,
            'punto_venta' => 3,
            'codigo_arca' => 11,
            'numero_comprobante' => 2,
            'estado_rendicion' => ArcaCaeaComprobante::ESTADO_RENDICION_INFORMADO,
        ]);
        ArcaCaeaComprobante::query()->create([
            'arca_caea_periodo_id' => $period->id,
            'punto_venta' => 3,
            'codigo_arca' => 11,
            'numero_comprobante' => 3,
            'estado_rendicion' => ArcaCaeaComprobante::ESTADO_RENDICION_OBSERVADO,
        ]);

        $summary = $period->load('comprobantes')->resumen_rendicion;

        $this->assertSame(3, $summary['total']);
        $this->assertSame(1, $summary['informados']);
        $this->assertSame(1, $summary['pendientes']);
        $this->assertSame(1, $summary['observados']);
        $this->assertSame(0, $summary['rechazados']);
    }
}
