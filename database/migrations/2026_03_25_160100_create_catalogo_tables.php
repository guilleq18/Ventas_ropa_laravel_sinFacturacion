<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->boolean('activa')->default(true);
        });

        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->foreignId('categoria_id')
                ->nullable()
                ->constrained('categorias')
                ->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->decimal('precio_base', 12, 2)->default(0);
            $table->decimal('costo_base', 12, 2)->default(0);
            $table->timestamps();

            $table->index('nombre');
        });

        Schema::create('atributos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->boolean('activo')->default(true);
        });

        Schema::create('atributo_valores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('atributo_id')
                ->constrained('atributos')
                ->cascadeOnDelete();
            $table->string('valor', 60);
            $table->boolean('activo')->default(true);

            $table->unique(['atributo_id', 'valor']);
            $table->index(['atributo_id', 'valor']);
        });

        Schema::create('variantes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('codigo_barras', 64)->nullable()->index();
            $table->decimal('precio', 12, 2)->default(0);
            $table->decimal('costo', 12, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['producto_id', 'activo']);
        });

        Schema::create('variante_atributos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variante_id')
                ->constrained('variantes')
                ->cascadeOnDelete();
            $table->foreignId('atributo_id')
                ->constrained('atributos')
                ->restrictOnDelete();
            $table->foreignId('valor_id')
                ->constrained('atributo_valores')
                ->restrictOnDelete();

            $table->unique(['variante_id', 'atributo_id']);
            $table->index(['variante_id', 'atributo_id']);
        });

        Schema::create('stock_sucursal', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->cascadeOnDelete();
            $table->foreignId('variante_id')
                ->constrained('variantes')
                ->cascadeOnDelete();
            $table->integer('cantidad')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['sucursal_id', 'variante_id']);
            $table->index('variante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sucursal');
        Schema::dropIfExists('variante_atributos');
        Schema::dropIfExists('variantes');
        Schema::dropIfExists('atributo_valores');
        Schema::dropIfExists('atributos');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
    }
};
