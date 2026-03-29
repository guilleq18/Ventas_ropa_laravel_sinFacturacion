<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->string('direccion', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('activa')->default(true);
        });

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->boolean('value_bool')->nullable();
            $table->integer('value_int')->nullable();
            $table->string('value_str', 255)->nullable();
            $table->string('description', 255)->default('');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->boolean('permitir_vender_sin_stock')->default(false);
            $table->boolean('permitir_cambiar_precio_venta')->default(false);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('sucursales')
                ->restrictOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('sucursales');
    }
};
