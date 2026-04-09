<?php

namespace Tests\Feature\Fiscal;

use App\Domain\Fiscal\Models\ArcaCaeaPeriodo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArcaCaeaPeriodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_caea_period_exposes_labels_and_operational_helpers(): void
    {
        $period = ArcaCaeaPeriodo::query()->create([
            'entorno' => ArcaCaeaPeriodo::ENTORNO_HOMOLOGACION,
            'cuit_representada' => '20364362634',
            'periodo' => 202604,
            'orden' => 1,
            'caea' => '12345678901234',
            'estado_solicitud' => ArcaCaeaPeriodo::ESTADO_SOLICITUD_AUTORIZADO,
            'estado_informacion' => ArcaCaeaPeriodo::ESTADO_INFORMACION_PENDIENTE,
            'vigente_desde' => now()->startOfMonth()->toDateString(),
            'vigente_hasta' => now()->startOfMonth()->addDays(14)->toDateString(),
            'fecha_tope_informar' => now()->addDays(10)->toDateString(),
            'fecha_proceso' => now()->toDateString(),
        ]);

        $this->assertSame('202604-1', $period->clave_periodo);
        $this->assertSame('04/2026', $period->periodo_label);
        $this->assertSame('1ra quincena', $period->orden_label);
        $this->assertSame('Autorizado', $period->estado_solicitud_label);
        $this->assertSame('Pendiente', $period->estado_informacion_label);
        $this->assertStringContainsString('al', $period->rango_vigencia_label);
        $this->assertTrue($period->esta_vigente);
        $this->assertFalse($period->informacion_vencida);
    }

    public function test_caea_period_marks_information_as_overdue_when_deadline_passed(): void
    {
        $period = ArcaCaeaPeriodo::query()->create([
            'entorno' => ArcaCaeaPeriodo::ENTORNO_PRODUCCION,
            'cuit_representada' => '20364362634',
            'periodo' => 202603,
            'orden' => 2,
            'caea' => '99999999999999',
            'estado_solicitud' => ArcaCaeaPeriodo::ESTADO_SOLICITUD_AUTORIZADO,
            'estado_informacion' => ArcaCaeaPeriodo::ESTADO_INFORMACION_PARCIAL,
            'vigente_desde' => now()->subMonth()->startOfMonth()->addDays(15)->toDateString(),
            'vigente_hasta' => now()->subMonth()->endOfMonth()->toDateString(),
            'fecha_tope_informar' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($period->esta_vigente);
        $this->assertTrue($period->informacion_vencida);
    }

    public function test_caea_period_unique_key_prevents_duplicates_for_same_environment_and_quincena(): void
    {
        ArcaCaeaPeriodo::query()->create([
            'entorno' => ArcaCaeaPeriodo::ENTORNO_HOMOLOGACION,
            'cuit_representada' => '20364362634',
            'periodo' => 202604,
            'orden' => 1,
            'caea' => '12345678901234',
        ]);

        $this->expectException(QueryException::class);

        ArcaCaeaPeriodo::query()->create([
            'entorno' => ArcaCaeaPeriodo::ENTORNO_HOMOLOGACION,
            'cuit_representada' => '20364362634',
            'periodo' => 202604,
            'orden' => 1,
            'caea' => '12345678901235',
        ]);
    }
}
