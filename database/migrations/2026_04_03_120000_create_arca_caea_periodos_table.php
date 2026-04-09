<?php

use App\Domain\Fiscal\Models\ArcaCaeaPeriodo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arca_caea_periodos', function (Blueprint $table): void {
            $table->id();
            $table->string('entorno', 20)->default(ArcaCaeaPeriodo::ENTORNO_HOMOLOGACION);
            $table->string('cuit_representada', 11);
            $table->unsignedInteger('periodo');
            $table->unsignedTinyInteger('orden');
            $table->string('caea', 20)->nullable();
            $table->string('estado_solicitud', 30)->default(ArcaCaeaPeriodo::ESTADO_SOLICITUD_AUTORIZADO);
            $table->string('estado_informacion', 30)->default(ArcaCaeaPeriodo::ESTADO_INFORMACION_PENDIENTE);
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->date('fecha_tope_informar')->nullable();
            $table->date('fecha_proceso')->nullable();
            $table->unsignedInteger('comprobantes_informados')->default(0);
            $table->dateTime('ultimo_informado_en')->nullable();
            $table->dateTime('sin_movimiento_informado_en')->nullable();
            $table->dateTime('ultimo_synced_at')->nullable();
            $table->longText('request_payload_json')->nullable();
            $table->longText('response_payload_json')->nullable();
            $table->longText('observaciones_arca_json')->nullable();
            $table->timestamps();

            $table->unique(
                ['entorno', 'cuit_representada', 'periodo', 'orden'],
                'arca_caea_periodos_entorno_cuit_periodo_orden_unique',
            );
            $table->index(['caea', 'estado_solicitud'], 'arca_caea_periodos_caea_estado_solicitud_idx');
            $table->index(['estado_informacion', 'fecha_tope_informar'], 'arca_caea_periodos_info_tope_idx');
            $table->index(['vigente_desde', 'vigente_hasta'], 'arca_caea_periodos_vigencia_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arca_caea_periodos');
    }
};
