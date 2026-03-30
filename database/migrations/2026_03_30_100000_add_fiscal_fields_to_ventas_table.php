<?php

use App\Domain\Ventas\Models\Venta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            $table->string('accion_fiscal', 40)
                ->default(Venta::ACCION_FISCAL_SOLO_REGISTRO)
                ->after('medio_pago');
            $table->string('estado_fiscal', 40)
                ->default(Venta::ESTADO_FISCAL_NO_REQUERIDO)
                ->after('accion_fiscal');
            $table->unsignedBigInteger('venta_comprobante_principal_id')
                ->nullable()
                ->after('estado_fiscal');
            $table->boolean('tiene_comprobante_fiscal')
                ->default(false)
                ->after('venta_comprobante_principal_id');

            $table->index(['accion_fiscal', 'estado_fiscal'], 'ventas_fiscal_idx');
            $table->index('venta_comprobante_principal_id', 'ventas_fiscal_principal_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            $table->dropIndex('ventas_fiscal_idx');
            $table->dropIndex('ventas_fiscal_principal_idx');
            $table->dropColumn([
                'accion_fiscal',
                'estado_fiscal',
                'venta_comprobante_principal_id',
                'tiene_comprobante_fiscal',
            ]);
        });
    }
};
