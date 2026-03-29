<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_cuenta_corriente', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cuenta_id')
                ->constrained('cuentas_corrientes')
                ->cascadeOnDelete();
            $table->string('tipo', 10);
            $table->decimal('monto', 12, 2);
            $table->dateTime('fecha')->useCurrent();
            $table->foreignId('venta_id')
                ->nullable()
                ->constrained('ventas')
                ->restrictOnDelete();
            $table->string('referencia', 120)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cuenta_id', 'tipo', 'fecha']);
            $table->index('venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_cuenta_corriente');
    }
};
