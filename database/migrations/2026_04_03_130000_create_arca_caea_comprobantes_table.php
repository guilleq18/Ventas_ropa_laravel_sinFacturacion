<?php

use App\Domain\Fiscal\Models\ArcaCaeaComprobante;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arca_caea_comprobantes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('arca_caea_periodo_id')
                ->constrained('arca_caea_periodos')
                ->cascadeOnDelete();
            $table->foreignId('venta_comprobante_id')
                ->nullable()
                ->constrained('venta_comprobantes')
                ->nullOnDelete();
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('sucursales')
                ->nullOnDelete();
            $table->unsignedInteger('punto_venta')->nullable();
            $table->unsignedSmallInteger('codigo_arca')->nullable();
            $table->unsignedBigInteger('numero_comprobante')->nullable();
            $table->date('fecha_emision')->nullable();
            $table->string('receptor_nombre', 160)->nullable();
            $table->string('doc_nro_receptor', 20)->nullable();
            $table->decimal('importe_total', 12, 2)->default(0);
            $table->string('estado_rendicion', 30)->default(ArcaCaeaComprobante::ESTADO_RENDICION_PENDIENTE);
            $table->dateTime('informado_en')->nullable();
            $table->longText('request_payload_json')->nullable();
            $table->longText('response_payload_json')->nullable();
            $table->longText('observaciones_arca_json')->nullable();
            $table->timestamps();

            $table->unique(
                ['arca_caea_periodo_id', 'punto_venta', 'codigo_arca', 'numero_comprobante'],
                'arca_caea_comprobantes_periodo_numero_unique',
            );
            $table->index(['estado_rendicion', 'informado_en'], 'arca_caea_comprobantes_estado_informado_idx');
            $table->index(['fecha_emision', 'sucursal_id'], 'arca_caea_comprobantes_fecha_sucursal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arca_caea_comprobantes');
    }
};
