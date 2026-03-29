<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_sesiones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->restrictOnDelete();
            $table->foreignId('cajero_apertura_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('abierta_en')->useCurrent();
            $table->foreignId('cajero_cierre_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('cerrada_en')->nullable();
            $table->unsignedTinyInteger('abierta_marker')->nullable()->default(1);

            $table->unique(['sucursal_id', 'abierta_marker'], 'caja_unica_abierta_por_sucursal');
            $table->index(['sucursal_id', 'abierta_en']);
            $table->index(['cajero_apertura_id', 'abierta_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_sesiones');
    }
};
