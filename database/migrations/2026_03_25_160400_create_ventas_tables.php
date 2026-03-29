<?php

use App\Domain\Ventas\Models\Venta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_cuotas', function (Blueprint $table): void {
            $table->id();
            $table->string('tarjeta', 30);
            $table->unsignedSmallInteger('cuotas');
            $table->decimal('recargo_pct', 5, 2)->default(0);
            $table->boolean('activo')->default(true);

            $table->unique(['tarjeta', 'cuotas']);
            $table->index(['activo', 'tarjeta', 'cuotas']);
        });

        Schema::create('ventas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->restrictOnDelete();
            $table->unsignedBigInteger('numero_sucursal')->nullable();
            $table->foreignId('caja_sesion_id')
                ->nullable()
                ->constrained('caja_sesiones')
                ->restrictOnDelete();
            $table->foreignId('cajero_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('fecha')->useCurrent();
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->restrictOnDelete();
            $table->string('estado', 20)->default(Venta::ESTADO_BORRADOR);
            $table->string('medio_pago', 30)->default(Venta::MEDIO_PAGO_EFECTIVO);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('empresa_nombre_snapshot', 80)->default('');
            $table->string('empresa_razon_social_snapshot', 120)->default('');
            $table->string('empresa_cuit_snapshot', 20)->default('');
            $table->string('empresa_direccion_snapshot', 255)->default('');
            $table->string('empresa_condicion_fiscal_snapshot', 40)->default('');
            $table->decimal('fiscal_items_sin_impuestos_nacionales', 12, 2)->nullable();
            $table->decimal('fiscal_items_iva_contenido', 12, 2)->nullable();
            $table->decimal('fiscal_items_otros_impuestos_nacionales_indirectos', 12, 2)->nullable();

            $table->unique(['sucursal_id', 'numero_sucursal'], 'ventas_numero_sucursal_uniq');
            $table->index(['sucursal_id', 'numero_sucursal']);
        });

        Schema::create('venta_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();
            $table->foreignId('variante_id')
                ->constrained('variantes')
                ->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('iva_alicuota_pct', 5, 2)->default(21);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('precio_unitario_sin_impuestos_nacionales', 12, 2)->nullable();
            $table->decimal('precio_unitario_iva_contenido', 12, 2)->nullable();
            $table->decimal('subtotal_sin_impuestos_nacionales', 12, 2)->nullable();
            $table->decimal('subtotal_iva_contenido', 12, 2)->nullable();
            $table->decimal('subtotal_otros_impuestos_nacionales_indirectos', 12, 2)->nullable();
        });

        Schema::create('venta_pagos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->decimal('monto', 12, 2);
            $table->unsignedSmallInteger('cuotas')->default(1);
            $table->decimal('coeficiente', 8, 4)->default(1);
            $table->decimal('recargo_pct', 5, 2)->default(0);
            $table->decimal('recargo_monto', 12, 2)->default(0);
            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('plan_cuotas')
                ->restrictOnDelete();
            $table->string('referencia', 120)->nullable();
            $table->string('pos_proveedor', 40)->nullable();
            $table->string('pos_terminal_id', 40)->nullable();
            $table->string('pos_lote', 40)->nullable();
            $table->string('pos_cupon', 40)->nullable();
            $table->string('pos_autorizacion', 40)->nullable();
            $table->string('pos_marca', 20)->nullable();
            $table->string('pos_ultimos4', 4)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['venta_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_pagos');
        Schema::dropIfExists('venta_items');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('plan_cuotas');
    }
};
