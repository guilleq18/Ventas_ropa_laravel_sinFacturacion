<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_comprobante_eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('venta_comprobante_id')
                ->constrained('venta_comprobantes')
                ->cascadeOnDelete();
            $table->string('tipo_evento', 60);
            $table->string('descripcion', 255)->default('');
            $table->longText('payload_json')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['venta_comprobante_id', 'tipo_evento'], 'venta_comprobante_eventos_tipo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_comprobante_eventos');
    }
};
