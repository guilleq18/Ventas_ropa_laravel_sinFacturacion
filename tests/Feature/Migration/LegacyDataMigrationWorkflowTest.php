<?php

namespace Tests\Feature\Migration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyDataMigrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_commands_can_export_import_and_validate_csv_migration(): void
    {
        $sourcePath = storage_path('framework/testing/legacy-source-'.Str::uuid().'.sqlite');
        $exportDir = storage_path('framework/testing/legacy-export-'.Str::uuid());
        $datasets = [
            'sucursales',
            'users',
            'categorias',
            'productos',
            'variantes',
            'stock_sucursal',
            'clientes',
            'cuentas_corrientes',
            'ventas',
            'venta_items',
            'venta_pagos',
            'movimientos_cuenta_corriente',
        ];

        try {
            File::ensureDirectoryExists(dirname($sourcePath));
            touch($sourcePath);

            config([
                'database.connections.legacy_source' => [
                    'driver' => 'sqlite',
                    'database' => $sourcePath,
                    'prefix' => '',
                    'foreign_key_constraints' => false,
                ],
            ]);
            DB::purge('legacy_source');

            $this->createLegacySourceSchema();
            $this->seedLegacySourceData();

            $this->artisan('migracion:exportar-django', [
                '--connection' => 'legacy_source',
                '--output' => $exportDir,
                '--only' => $datasets,
            ])->assertExitCode(0);

            $this->assertFileExists($exportDir.DIRECTORY_SEPARATOR.'ventas.csv');
            $this->assertFileExists($exportDir.DIRECTORY_SEPARATOR.'manifest.json');

            $this->artisan('migracion:importar-csv', [
                'path' => $exportDir,
                '--truncate' => true,
                '--only' => $datasets,
            ])->assertExitCode(0);

            $this->assertDatabaseHas('users', [
                'id' => 10,
                'username' => 'operador',
                'email' => 'operador@legacy.local',
            ]);
            $this->assertDatabaseHas('ventas', [
                'id' => 70,
                'sucursal_id' => 1,
                'cliente_id' => 40,
                'estado' => 'CONFIRMADA',
                'total' => '110.00',
            ]);
            $this->assertDatabaseHas('stock_sucursal', [
                'id' => 30,
                'sucursal_id' => 1,
                'variante_id' => 20,
                'cantidad' => 4,
            ]);
            $this->assertDatabaseHas('movimientos_cuenta_corriente', [
                'id' => 100,
                'cuenta_id' => 50,
                'tipo' => 'DEBITO',
                'monto' => '30.00',
                'venta_id' => 70,
            ]);

            $this->artisan('migracion:validar-csv', [
                'path' => $exportDir,
                '--only' => ['ventas', 'movimientos_cuenta_corriente', 'stock_sucursal'],
            ])
                ->expectsOutputToContain('Validacion OK.')
                ->assertExitCode(0);
        } finally {
            DB::purge('legacy_source');
            File::delete($sourcePath);
            File::deleteDirectory($exportDir);
        }
    }

    protected function createLegacySourceSchema(): void
    {
        Schema::connection('legacy_source')->create('core_sucursal', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->boolean('activa');
        });

        Schema::connection('legacy_source')->create('auth_user', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('username');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('is_active');
            $table->dateTime('date_joined');
            $table->dateTime('last_login')->nullable();
        });

        Schema::connection('legacy_source')->create('catalogo_categoria', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('nombre');
            $table->boolean('activa');
        });

        Schema::connection('legacy_source')->create('catalogo_producto', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->integer('categoria_id')->nullable();
            $table->boolean('activo');
            $table->decimal('precio_base', 12, 2);
            $table->decimal('costo_base', 12, 2);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        Schema::connection('legacy_source')->create('catalogo_variante', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('producto_id');
            $table->string('sku');
            $table->string('codigo_barras')->nullable();
            $table->decimal('precio', 12, 2);
            $table->decimal('costo', 12, 2);
            $table->boolean('activo');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        Schema::connection('legacy_source')->create('catalogo_stocksucursal', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('sucursal_id');
            $table->integer('variante_id');
            $table->integer('cantidad');
            $table->dateTime('updated_at')->nullable();
        });

        Schema::connection('legacy_source')->create('cuentas_corrientes_cliente', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('dni');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->boolean('activo');
            $table->dateTime('creado_en');
        });

        Schema::connection('legacy_source')->create('cuentas_corrientes_cuentacorriente', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('cliente_id');
            $table->boolean('activa');
            $table->dateTime('creada_en');
        });

        Schema::connection('legacy_source')->create('ventas_venta', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('sucursal_id');
            $table->integer('numero_sucursal')->nullable();
            $table->integer('caja_sesion_id')->nullable();
            $table->integer('cajero_id')->nullable();
            $table->dateTime('fecha');
            $table->integer('cliente_id')->nullable();
            $table->string('estado');
            $table->string('medio_pago');
            $table->decimal('total', 12, 2);
            $table->string('empresa_nombre_snapshot')->default('');
            $table->string('empresa_razon_social_snapshot')->default('');
            $table->string('empresa_cuit_snapshot')->default('');
            $table->string('empresa_direccion_snapshot')->default('');
            $table->string('empresa_condicion_fiscal_snapshot')->default('');
            $table->decimal('fiscal_items_sin_impuestos_nacionales', 12, 2)->nullable();
            $table->decimal('fiscal_items_iva_contenido', 12, 2)->nullable();
            $table->decimal('fiscal_items_otros_impuestos_nacionales_indirectos', 12, 2)->nullable();
        });

        Schema::connection('legacy_source')->create('ventas_ventaitem', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('venta_id');
            $table->integer('variante_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('iva_alicuota_pct', 5, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('precio_unitario_sin_impuestos_nacionales', 12, 2)->nullable();
            $table->decimal('precio_unitario_iva_contenido', 12, 2)->nullable();
            $table->decimal('subtotal_sin_impuestos_nacionales', 12, 2)->nullable();
            $table->decimal('subtotal_iva_contenido', 12, 2)->nullable();
            $table->decimal('subtotal_otros_impuestos_nacionales_indirectos', 12, 2)->nullable();
        });

        Schema::connection('legacy_source')->create('ventas_ventapago', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('venta_id');
            $table->string('tipo');
            $table->decimal('monto', 12, 2);
            $table->integer('cuotas');
            $table->decimal('coeficiente', 8, 4);
            $table->decimal('recargo_pct', 5, 2);
            $table->decimal('recargo_monto', 12, 2);
            $table->integer('plan_id')->nullable();
            $table->string('referencia')->nullable();
            $table->string('pos_proveedor')->nullable();
            $table->string('pos_terminal_id')->nullable();
            $table->string('pos_lote')->nullable();
            $table->string('pos_cupon')->nullable();
            $table->string('pos_autorizacion')->nullable();
            $table->string('pos_marca')->nullable();
            $table->string('pos_ultimos4')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::connection('legacy_source')->create('cuentas_corrientes_movimientocuentacorriente', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('cuenta_id');
            $table->string('tipo');
            $table->decimal('monto', 12, 2);
            $table->dateTime('fecha');
            $table->integer('venta_id')->nullable();
            $table->string('referencia')->nullable();
            $table->text('observacion')->nullable();
            $table->dateTime('created_at')->nullable();
        });
    }

    protected function seedLegacySourceData(): void
    {
        DB::connection('legacy_source')->table('core_sucursal')->insert([
            'id' => 1,
            'nombre' => 'Casa Central',
            'direccion' => 'San Martin 123',
            'telefono' => '3515550000',
            'activa' => 1,
        ]);

        DB::connection('legacy_source')->table('auth_user')->insert([
            'id' => 10,
            'username' => 'operador',
            'first_name' => 'Mica',
            'last_name' => 'Perez',
            'email' => '',
            'password' => '$2y$12$legacyhash',
            'is_active' => 1,
            'date_joined' => '2026-03-20 10:00:00',
            'last_login' => '2026-03-25 08:30:00',
        ]);

        DB::connection('legacy_source')->table('catalogo_categoria')->insert([
            'id' => 5,
            'nombre' => 'Abrigos',
            'activa' => 1,
        ]);
        DB::connection('legacy_source')->table('catalogo_producto')->insert([
            'id' => 12,
            'nombre' => 'Campera Nevada',
            'descripcion' => 'Modelo legacy',
            'categoria_id' => 5,
            'activo' => 1,
            'precio_base' => '100.00',
            'costo_base' => '60.00',
            'created_at' => '2026-03-20 11:00:00',
            'updated_at' => '2026-03-25 09:00:00',
        ]);
        DB::connection('legacy_source')->table('catalogo_variante')->insert([
            'id' => 20,
            'producto_id' => 12,
            'sku' => 'CAMP-NEV-42',
            'codigo_barras' => '7791234567890',
            'precio' => '100.00',
            'costo' => '60.00',
            'activo' => 1,
            'created_at' => '2026-03-20 11:05:00',
            'updated_at' => '2026-03-25 09:05:00',
        ]);
        DB::connection('legacy_source')->table('catalogo_stocksucursal')->insert([
            'id' => 30,
            'sucursal_id' => 1,
            'variante_id' => 20,
            'cantidad' => 4,
            'updated_at' => '2026-03-25 09:10:00',
        ]);

        DB::connection('legacy_source')->table('cuentas_corrientes_cliente')->insert([
            'id' => 40,
            'dni' => '30123456',
            'nombre' => 'Lucia',
            'apellido' => 'Fernandez',
            'telefono' => '3514441111',
            'direccion' => 'Belgrano 200',
            'fecha_nacimiento' => '1990-05-10',
            'activo' => 1,
            'creado_en' => '2026-03-21 10:00:00',
        ]);
        DB::connection('legacy_source')->table('cuentas_corrientes_cuentacorriente')->insert([
            'id' => 50,
            'cliente_id' => 40,
            'activa' => 1,
            'creada_en' => '2026-03-21 10:05:00',
        ]);

        DB::connection('legacy_source')->table('ventas_venta')->insert([
            'id' => 70,
            'sucursal_id' => 1,
            'numero_sucursal' => 8,
            'caja_sesion_id' => null,
            'cajero_id' => 10,
            'fecha' => '2026-03-25 12:00:00',
            'cliente_id' => 40,
            'estado' => 'CONFIRMADA',
            'medio_pago' => 'MIXTO',
            'total' => '110.00',
            'empresa_nombre_snapshot' => 'VGC Legacy',
            'empresa_razon_social_snapshot' => 'VGC Legacy SRL',
            'empresa_cuit_snapshot' => '20-11111111-1',
            'empresa_direccion_snapshot' => 'San Martin 123',
            'empresa_condicion_fiscal_snapshot' => 'RESPONSABLE_INSCRIPTO',
            'fiscal_items_sin_impuestos_nacionales' => '82.64',
            'fiscal_items_iva_contenido' => '17.36',
            'fiscal_items_otros_impuestos_nacionales_indirectos' => '0.00',
        ]);
        DB::connection('legacy_source')->table('ventas_ventaitem')->insert([
            'id' => 80,
            'venta_id' => 70,
            'variante_id' => 20,
            'cantidad' => 1,
            'precio_unitario' => '100.00',
            'iva_alicuota_pct' => '21.00',
            'subtotal' => '100.00',
            'precio_unitario_sin_impuestos_nacionales' => '82.64',
            'precio_unitario_iva_contenido' => '17.36',
            'subtotal_sin_impuestos_nacionales' => '82.64',
            'subtotal_iva_contenido' => '17.36',
            'subtotal_otros_impuestos_nacionales_indirectos' => '0.00',
        ]);
        DB::connection('legacy_source')->table('ventas_ventapago')->insert([
            'id' => 90,
            'venta_id' => 70,
            'tipo' => 'CREDITO',
            'monto' => '100.00',
            'cuotas' => 3,
            'coeficiente' => '1.1000',
            'recargo_pct' => '10.00',
            'recargo_monto' => '10.00',
            'plan_id' => null,
            'referencia' => 'Cupon 123',
            'pos_proveedor' => null,
            'pos_terminal_id' => null,
            'pos_lote' => null,
            'pos_cupon' => null,
            'pos_autorizacion' => null,
            'pos_marca' => null,
            'pos_ultimos4' => null,
            'created_at' => '2026-03-25 12:01:00',
        ]);
        DB::connection('legacy_source')->table('cuentas_corrientes_movimientocuentacorriente')->insert([
            'id' => 100,
            'cuenta_id' => 50,
            'tipo' => 'DEBITO',
            'monto' => '30.00',
            'fecha' => '2026-03-25 12:05:00',
            'venta_id' => 70,
            'referencia' => 'Venta #70',
            'observacion' => 'Migracion legacy',
            'created_at' => '2026-03-25 12:05:00',
        ]);
    }
}
