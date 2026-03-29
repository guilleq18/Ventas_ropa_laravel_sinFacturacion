<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->string('dni', 20)->unique();
            $table->string('nombre', 80);
            $table->string('apellido', 80);
            $table->string('telefono', 40)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('creado_en')->useCurrent();

            $table->index('dni');
            $table->index(['apellido', 'nombre']);
        });

        Schema::create('cuentas_corrientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')
                ->unique()
                ->constrained('clientes')
                ->restrictOnDelete();
            $table->boolean('activa')->default(true);
            $table->timestamp('creada_en')->useCurrent();

            $table->index('activa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_corrientes');
        Schema::dropIfExists('clientes');
    }
};
