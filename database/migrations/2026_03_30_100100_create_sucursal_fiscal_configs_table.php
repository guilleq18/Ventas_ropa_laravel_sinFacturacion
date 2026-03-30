<?php

use App\Domain\Fiscal\Models\SucursalFiscalConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursal_fiscal_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sucursal_id')
                ->unique()
                ->constrained('sucursales')
                ->cascadeOnDelete();
            $table->string('modo_operacion', 40)
                ->default(SucursalFiscalConfig::MODO_SOLO_REGISTRO);
            $table->string('entorno', 20)
                ->default(SucursalFiscalConfig::ENTORNO_HOMOLOGACION);
            $table->unsignedInteger('punto_venta')->nullable();
            $table->boolean('facturacion_habilitada')->default(false);
            $table->boolean('requiere_receptor_en_todas')->default(false);
            $table->string('domicilio_fiscal_emision', 255)->nullable();
            $table->dateTime('ultimo_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursal_fiscal_configs');
    }
};
