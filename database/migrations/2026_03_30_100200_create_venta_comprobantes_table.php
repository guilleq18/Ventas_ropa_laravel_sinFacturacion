<?php

use App\Domain\Fiscal\Models\VentaComprobante;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_comprobantes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->restrictOnDelete();
            $table->string('modo_emision', 40);
            $table->string('estado', 40)
                ->default(VentaComprobante::ESTADO_BORRADOR);
            $table->string('tipo_comprobante', 20)
                ->default(VentaComprobante::TIPO_FACTURA);
            $table->string('clase', 5)->nullable();
            $table->unsignedSmallInteger('codigo_arca')->nullable();
            $table->unsignedInteger('punto_venta')->nullable();
            $table->unsignedBigInteger('numero_comprobante')->nullable();
            $table->dateTime('fecha_emision');
            $table->string('moneda', 10)->default('PES');
            $table->decimal('cotizacion_moneda', 12, 6)->nullable();
            $table->unsignedSmallInteger('doc_tipo_receptor')->nullable();
            $table->string('doc_nro_receptor', 20)->nullable();
            $table->string('receptor_nombre', 160)->nullable();
            $table->string('receptor_condicion_iva', 40)->nullable();
            $table->string('receptor_domicilio', 255)->nullable();
            $table->decimal('importe_neto', 12, 2)->default(0);
            $table->decimal('importe_iva', 12, 2)->default(0);
            $table->decimal('importe_otros_tributos', 12, 2)->default(0);
            $table->decimal('importe_total', 12, 2)->default(0);
            $table->string('cae', 20)->nullable();
            $table->date('cae_vto')->nullable();
            $table->longText('qr_payload_json')->nullable();
            $table->text('qr_url')->nullable();
            $table->string('referencia_externa_tipo', 40)->nullable();
            $table->string('referencia_externa_numero', 80)->nullable();
            $table->string('resultado_arca', 20)->nullable();
            $table->longText('observaciones_arca_json')->nullable();
            $table->longText('request_payload_json')->nullable();
            $table->longText('response_payload_json')->nullable();
            $table->dateTime('emitido_en')->nullable();
            $table->timestamps();

            $table->index(['venta_id', 'estado']);
            $table->index(['sucursal_id', 'fecha_emision']);
            $table->unique(['punto_venta', 'codigo_arca', 'numero_comprobante'], 'venta_comprobantes_fiscal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_comprobantes');
    }
};
