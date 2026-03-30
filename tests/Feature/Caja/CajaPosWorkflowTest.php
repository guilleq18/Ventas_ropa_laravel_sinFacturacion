<?php

namespace Tests\Feature\Caja;

use App\Domain\Admin\Models\UserProfile;
use App\Domain\Caja\Models\CajaSesion;
use App\Domain\Catalogo\Models\Categoria;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Models\StockSucursal;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Core\Models\AppSetting;
use App\Domain\Core\Models\Sucursal;
use App\Domain\Fiscal\Models\SucursalFiscalConfig;
use App\Domain\Fiscal\Models\VentaComprobante;
use App\Domain\Fiscal\Support\FiscalConfigManager;
use App\Domain\Fiscal\Support\FiscalDocumentBuilder;
use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente;
use App\Domain\Ventas\Models\PlanCuotas;
use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Models\VentaPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaPosWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_search_scan_and_manage_cart(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();
        $variant = $this->createVariantFixture($branch, '100.00', 5);

        $this->actingAs($user)
            ->get(route('caja.pos'))
            ->assertOk()
            ->assertSee('Caja POS')
            ->assertSee('Casa Central');

        $this->actingAs($user)
            ->post(route('caja.abrir'))
            ->assertRedirect(route('caja.pos'));

        $this->assertDatabaseHas('caja_sesiones', [
            'sucursal_id' => $branch->id,
            'cajero_apertura_id' => $user->id,
            'abierta_marker' => 1,
        ]);

        $this->actingAs($user)
            ->withHeader('HX-Request', 'true')
            ->get(route('caja.buscar', ['q' => 'Campera']))
            ->assertOk()
            ->assertSee('Campera Nevada');

        $this->actingAs($user)
            ->post(route('caja.scan'), ['q' => 'CAMP-NEV-42'])
            ->assertRedirect(route('caja.pos'));

        $cart = $this->app['session.store']->get('pos_cart', []);
        $this->assertSame(1, $cart[(string) $variant->id]['qty']);

        $this->actingAs($user)
            ->post(route('caja.carrito.qty', $variant), ['qty' => 3])
            ->assertRedirect(route('caja.pos'));

        $cart = $this->app['session.store']->get('pos_cart', []);
        $this->assertSame(3, $cart[(string) $variant->id]['qty']);

        $this->actingAs($user)
            ->post(route('caja.carrito.precio', $variant), ['precio' => '150.00'])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('error');

        $cart = $this->app['session.store']->get('pos_cart', []);
        $this->assertSame('100.00', $cart[(string) $variant->id]['precio']);

        AppSetting::query()->create([
            'key' => "ventas.sucursal.{$branch->id}.permitir_cambiar_precio_venta",
            'value_bool' => true,
            'description' => 'Permite cambiar el precio de venta en POS',
        ]);

        $this->actingAs($user)
            ->post(route('caja.carrito.precio', $variant), ['precio' => '150.00'])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('success');

        $cart = $this->app['session.store']->get('pos_cart', []);
        $this->assertSame('150.00', $cart[(string) $variant->id]['precio']);
    }

    public function test_payment_modal_renders_new_payment_rows_ready_for_editing(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();
        $variant = $this->createVariantFixture($branch, '100.00', 5);

        $this->actingAs($user)->post(route('caja.abrir'));
        $this->actingAs($user)->post(route('caja.carrito.agregar', $variant));

        $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->post(route('caja.pagos.agregar'))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Se agrego una nueva linea de pago.',
            ]);

        $payments = $this->app['session.store']->get('pos_payments', []);

        $this->assertCount(1, $payments);

        $this->actingAs($user)
            ->get(route('caja.pos', ['modal' => 'pagos']))
            ->assertOk()
            ->assertSee(route('caja.pagos.update', 0), false)
            ->assertSee('class="payment-row-card"', false)
            ->assertSee('name="monto" value="0.00"', false);
    }

    public function test_payment_modal_keeps_add_button_visible_when_cart_is_empty(): void
    {
        [$user] = $this->createCashierWithBranch();

        $this->actingAs($user)
            ->get(route('caja.pos', ['modal' => 'pagos']))
            ->assertOk()
            ->assertSee('data-payment-action="add"', false)
            ->assertSee('title="Agrega productos al carrito para habilitar pagos."', false)
            ->assertSee('disabled', false);
    }

    public function test_pos_renders_fiscal_draft_modal_and_f4_shortcut_when_receiver_data_is_required(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();
        $variant = $this->createVariantFixture($branch, '100.00', 5);

        SucursalFiscalConfig::query()->create([
            'sucursal_id' => $branch->id,
            'modo_operacion' => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
            'entorno' => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
            'punto_venta' => 3,
            'facturacion_habilitada' => true,
            'requiere_receptor_en_todas' => true,
        ]);

        $this->actingAs($user)->post(route('caja.abrir'));
        $this->actingAs($user)->post(route('caja.carrito.agregar', $variant));
        $this->actingAs($user)->post(route('caja.pagos.agregar'));
        $this->actingAs($user)->post(route('caja.pagos.update', 0), [
            'tipo' => 'CONTADO',
            'monto' => '100.00',
        ]);

        $this->actingAs($user)
            ->get(route('caja.pos'))
            ->assertOk()
            ->assertSee('id="fiscal_confirm_modal"', false)
            ->assertSee('data-save-fiscal-draft-url=', false)
            ->assertSee('id="btn_open_fiscal"', false)
            ->assertSee('type="submit" title="Confirmar venta (F5)"', false)
            ->assertSee('Datos fiscales')
            ->assertSee('Condición IVA receptor')
            ->assertSee('Aceptar datos fiscales')
            ->assertSee('Se va a validar documento, condición IVA, nombre y domicilio antes de confirmar.')
            ->assertDontSee('Borrador fiscal activo')
            ->assertDontSee('Solicita autorización a ARCA, guarda CAE y genera el comprobante fiscal imprimible.');
    }

    public function test_cashier_can_save_fiscal_draft_for_reuse(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();

        SucursalFiscalConfig::query()->create([
            'sucursal_id' => $branch->id,
            'modo_operacion' => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
            'entorno' => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
            'punto_venta' => 3,
            'facturacion_habilitada' => true,
            'requiere_receptor_en_todas' => false,
        ]);

        $this->actingAs($user)->post(route('caja.abrir'));

        $this->actingAs($user)
            ->postJson(route('caja.fiscal-draft.save'), [
                'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
                'fiscal_receptor_doc_tipo' => 'CUIT',
                'fiscal_receptor_doc_nro' => '30712345678',
                'fiscal_receptor_nombre' => 'Empresa Demo SA',
                'fiscal_receptor_domicilio' => 'Mitre 123',
                'fiscal_receptor_condicion_iva' => 'IVA_RESPONSABLE_INSCRIPTO',
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ])
            ->assertJsonPath('draft.accion_fiscal', Venta::ACCION_FISCAL_FACTURA_ELECTRONICA)
            ->assertJsonPath('draft.fiscal_receptor_doc_tipo', 'CUIT')
            ->assertJsonPath('draft.fiscal_receptor_condicion_iva', 'IVA_RESPONSABLE_INSCRIPTO');

        $this->assertSame([
            'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
            'referencia_comprobante_externo' => '',
            'fiscal_receptor_doc_tipo' => 'CUIT',
            'fiscal_receptor_doc_nro' => '30712345678',
            'fiscal_receptor_nombre' => 'Empresa Demo SA',
            'fiscal_receptor_domicilio' => 'Mitre 123',
            'fiscal_receptor_condicion_iva' => 'IVA_RESPONSABLE_INSCRIPTO',
        ], $this->app['session.store']->get('pos_fiscal_draft'));
    }

    public function test_confirming_sale_persists_sale_stock_and_current_account_movement(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();
        $variant = $this->createVariantFixture($branch, '100.00', 5);
        $plan = PlanCuotas::query()->create([
            'tarjeta' => 'VISA',
            'cuotas' => 3,
            'recargo_pct' => '10.00',
            'activo' => true,
        ]);
        $client = Cliente::query()->create([
            'dni' => '30111222',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'activo' => true,
        ]);
        CuentaCorriente::query()->create([
            'cliente_id' => $client->id,
            'activa' => true,
        ]);
        AppSetting::query()->create([
            'key' => 'empresa.nombre',
            'value_str' => 'Tienda Urbana',
            'description' => 'Nombre comercial',
        ]);
        AppSetting::query()->create([
            'key' => 'empresa.cuit',
            'value_str' => '30-12345678-9',
            'description' => 'CUIT',
        ]);
        Venta::query()->create([
            'sucursal_id' => $branch->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'medio_pago' => Venta::MEDIO_PAGO_EFECTIVO,
            'total' => '80.00',
            'numero_sucursal' => 7,
        ]);

        $this->actingAs($user)->post(route('caja.abrir'));
        $this->actingAs($user)->post(route('caja.carrito.agregar', $variant));

        $this->actingAs($user)
            ->post(route('caja.pagos.agregar'))
            ->assertRedirect(route('caja.pos'));
        $this->actingAs($user)
            ->post(route('caja.pagos.update', 0), [
                'tipo' => 'CREDITO',
                'monto' => '50.00',
                'plan_id' => $plan->id,
            ])
            ->assertRedirect(route('caja.pos'));

        $this->actingAs($user)
            ->post(route('caja.pagos.agregar'))
            ->assertRedirect(route('caja.pos'));
        $this->actingAs($user)
            ->post(route('caja.pagos.update', 1), [
                'tipo' => 'CUENTA_CORRIENTE',
                'monto' => '50.00',
                'cc_cliente_id' => $client->id,
            ])
            ->assertRedirect(route('caja.pos'));

        $this->actingAs($user)
            ->get(route('caja.pos'))
            ->assertOk()
            ->assertSee('Saldo actual');

        $token = $this->app['session.store']->get('pos_confirm_token');

        $this->actingAs($user)
            ->post(route('caja.confirmar'), [
                'confirm_token' => $token,
            ])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('success');

        $newToken = $this->app['session.store']->get('pos_confirm_token');
        $sale = Venta::query()
            ->with(['items', 'pagos', 'movimientosCuentaCorriente'])
            ->where('numero_sucursal', 8)
            ->firstOrFail();

        $this->assertNotSame($token, $newToken);
        $this->assertSame(Venta::ESTADO_CONFIRMADA, $sale->estado);
        $this->assertSame(Venta::MEDIO_PAGO_MIXTO, $sale->medio_pago);
        $this->assertSame(Venta::ACCION_FISCAL_SOLO_REGISTRO, $sale->accion_fiscal);
        $this->assertSame(Venta::ESTADO_FISCAL_NO_REQUERIDO, $sale->estado_fiscal);
        $this->assertFalse($sale->tiene_comprobante_fiscal);
        $this->assertNull($sale->venta_comprobante_principal_id);
        $this->assertSame($client->id, $sale->cliente_id);
        $this->assertSame('105.00', $sale->total);
        $this->assertSame('Tienda Urbana', $sale->empresa_nombre_snapshot);
        $this->assertSame('30-12345678-9', $sale->empresa_cuit_snapshot);
        $this->assertCount(1, $sale->items);
        $this->assertCount(2, $sale->pagos);
        $this->assertCount(1, $sale->movimientosCuentaCorriente);
        $this->assertDatabaseHas('stock_sucursal', [
            'sucursal_id' => $branch->id,
            'variante_id' => $variant->id,
            'cantidad' => 4,
        ]);
        $this->assertDatabaseHas('venta_pagos', [
            'venta_id' => $sale->id,
            'tipo' => VentaPago::TIPO_CREDITO,
            'monto' => '50.00',
            'recargo_monto' => '5.00',
        ]);
        $this->assertDatabaseHas('movimientos_cuenta_corriente', [
            'venta_id' => $sale->id,
            'tipo' => MovimientoCuentaCorriente::TIPO_DEBITO,
            'monto' => '50.00',
        ]);
        $this->assertSame([], $this->app['session.store']->get('pos_cart', []));
        $this->assertSame([], $this->app['session.store']->get('pos_payments', []));
        $this->assertDatabaseCount('venta_comprobantes', 0);

        $this->actingAs($user)
            ->get(route('caja.pos'))
            ->assertOk()
            ->assertSee(route('caja.ticket', $sale))
            ->assertSee(route('caja.ticket', $sale).'?print=1')
            ->assertSee('data-label="Subtotal"', false);

        $this->actingAs($user)
            ->post(route('caja.confirmar'), [
                'confirm_token' => $token,
            ])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('error');

        $this->assertSame(2, Venta::query()->count());
    }

    public function test_confirming_sale_rejects_multiple_current_account_clients(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();
        $variant = $this->createVariantFixture($branch, '100.00', 5);
        $clientA = Cliente::query()->create([
            'dni' => '30111222',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'activo' => true,
        ]);
        $clientB = Cliente::query()->create([
            'dni' => '30999888',
            'nombre' => 'Luis',
            'apellido' => 'Perez',
            'activo' => true,
        ]);
        CuentaCorriente::query()->create([
            'cliente_id' => $clientA->id,
            'activa' => true,
        ]);
        CuentaCorriente::query()->create([
            'cliente_id' => $clientB->id,
            'activa' => true,
        ]);

        $this->actingAs($user)->post(route('caja.abrir'));
        $this->actingAs($user)->post(route('caja.carrito.agregar', $variant));

        $this->actingAs($user)
            ->post(route('caja.pagos.agregar'))
            ->assertRedirect(route('caja.pos'));
        $this->actingAs($user)
            ->post(route('caja.pagos.update', 0), [
                'tipo' => 'CUENTA_CORRIENTE',
                'monto' => '50.00',
                'cc_cliente_id' => $clientA->id,
            ])
            ->assertRedirect(route('caja.pos'));

        $this->actingAs($user)
            ->post(route('caja.pagos.agregar'))
            ->assertRedirect(route('caja.pos'));
        $this->actingAs($user)
            ->post(route('caja.pagos.update', 1), [
                'tipo' => 'CUENTA_CORRIENTE',
                'monto' => '50.00',
                'cc_cliente_id' => $clientB->id,
            ])
            ->assertRedirect(route('caja.pos'));

        $token = $this->app['session.store']->get('pos_confirm_token');

        $this->actingAs($user)
            ->post(route('caja.confirmar'), [
                'confirm_token' => $token,
            ])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('error', 'Cuenta corriente: todos los pagos deben corresponder al mismo cliente.');

        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('movimientos_cuenta_corriente', 0);
        $this->assertDatabaseHas('stock_sucursal', [
            'sucursal_id' => $branch->id,
            'variante_id' => $variant->id,
            'cantidad' => 5,
        ]);
    }

    public function test_confirming_sale_can_store_external_referenced_invoice_data(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();
        $variant = $this->createVariantFixture($branch, '100.00', 5);

        SucursalFiscalConfig::query()->create([
            'sucursal_id' => $branch->id,
            'modo_operacion' => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
            'entorno' => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
            'punto_venta' => 3,
            'facturacion_habilitada' => false,
        ]);

        $this->actingAs($user)->post(route('caja.abrir'));
        $this->actingAs($user)->post(route('caja.carrito.agregar', $variant));
        $this->actingAs($user)->post(route('caja.pagos.agregar'));
        $this->actingAs($user)->post(route('caja.pagos.update', 0), [
            'tipo' => 'CONTADO',
            'monto' => '100.00',
        ]);

        $token = $this->app['session.store']->get('pos_confirm_token');

        $this->actingAs($user)
            ->post(route('caja.confirmar'), [
                'confirm_token' => $token,
                'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_EXTERNA_REFERENCIADA,
                'referencia_comprobante_externo' => 'CLI-0003-00001234',
            ])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('success');

        $sale = Venta::query()->with('comprobantePrincipal')->firstOrFail();

        $this->assertSame(Venta::ACCION_FISCAL_FACTURA_EXTERNA_REFERENCIADA, $sale->accion_fiscal);
        $this->assertSame(Venta::ESTADO_FISCAL_EXTERNO_REFERENCIADO, $sale->estado_fiscal);
        $this->assertTrue($sale->tiene_comprobante_fiscal);
        $this->assertNotNull($sale->venta_comprobante_principal_id);
        $this->assertSame(VentaComprobante::MODO_EXTERNA_REFERENCIADA, $sale->comprobantePrincipal?->modo_emision);
        $this->assertSame(VentaComprobante::ESTADO_REFERENCIADO, $sale->comprobantePrincipal?->estado);
        $this->assertSame('CLI-0003-00001234', $sale->comprobantePrincipal?->referencia_externa_numero);
        $this->assertDatabaseHas('venta_comprobantes', [
            'venta_id' => $sale->id,
            'modo_emision' => VentaComprobante::MODO_EXTERNA_REFERENCIADA,
            'estado' => VentaComprobante::ESTADO_REFERENCIADO,
            'referencia_externa_numero' => 'CLI-0003-00001234',
        ]);
    }

    public function test_confirming_sale_can_authorize_electronic_invoice_and_expose_printable_document(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();
        $variant = $this->createVariantFixture($branch, '12100.00', 5);

        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.nombre'],
            ['value_str' => 'Tienda Urbana', 'description' => 'Nombre comercial'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.razon_social'],
            ['value_str' => 'Tienda Urbana SRL', 'description' => 'Razón social'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.cuit'],
            ['value_str' => '30-12345678-9', 'description' => 'CUIT'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.direccion'],
            ['value_str' => 'Av. Siempre Viva 742', 'description' => 'Dirección'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.condicion_fiscal'],
            ['value_str' => 'RESPONSABLE_INSCRIPTO', 'description' => 'Condición fiscal'],
        );

        SucursalFiscalConfig::query()->create([
            'sucursal_id' => $branch->id,
            'modo_operacion' => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
            'entorno' => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
            'punto_venta' => 3,
            'facturacion_habilitada' => true,
            'requiere_receptor_en_todas' => false,
        ]);

        $this->actingAs($user)->post(route('caja.abrir'));
        $this->actingAs($user)->post(route('caja.carrito.agregar', $variant));
        $this->actingAs($user)->post(route('caja.pagos.agregar'));
        $this->actingAs($user)->post(route('caja.pagos.update', 0), [
            'tipo' => 'CONTADO',
            'monto' => '12100.00',
        ]);

        $token = $this->app['session.store']->get('pos_confirm_token');

        $this->actingAs($user)
            ->post(route('caja.confirmar'), [
                'confirm_token' => $token,
                'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
                'fiscal_receptor_doc_tipo' => 'DNI',
                'fiscal_receptor_doc_nro' => '30111222',
                'fiscal_receptor_nombre' => 'Ana Lopez',
                'fiscal_receptor_domicilio' => 'Belgrano 123',
            ])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('success');

        $sale = Venta::query()->with('comprobantePrincipal')->firstOrFail();
        $document = $sale->comprobantePrincipal;

        $this->assertSame(Venta::ACCION_FISCAL_FACTURA_ELECTRONICA, $sale->accion_fiscal);
        $this->assertSame(Venta::ESTADO_FISCAL_AUTORIZADO, $sale->estado_fiscal);
        $this->assertTrue($sale->tiene_comprobante_fiscal);
        $this->assertNotNull($document);
        $this->assertSame(VentaComprobante::MODO_ELECTRONICA_ARCA, $document?->modo_emision);
        $this->assertSame(VentaComprobante::ESTADO_AUTORIZADO, $document?->estado);
        $this->assertSame('B', $document?->clase);
        $this->assertSame(6, $document?->codigo_arca);
        $this->assertSame(3, $document?->punto_venta);
        $this->assertSame('99990000123456', $document?->cae);
        $this->assertNotNull($document?->qr_url);
        $this->assertStringContainsString('https://www.arca.gob.ar/fe/qr/?p=', (string) $document?->qr_url);

        $this->actingAs($user)
            ->get(route('caja.pos'))
            ->assertOk()
            ->assertSee(route('fiscal.comprobantes.show', $document).'?print=1');

        $this->actingAs($user)
            ->get(route('fiscal.comprobantes.show', $document))
            ->assertOk()
            ->assertSee('Factura B')
            ->assertSee('99990000123456');
    }

    public function test_electronic_invoice_defaults_to_consumer_final_vat_condition_when_receiver_data_is_not_provided(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();
        $variant = $this->createVariantFixture($branch, '100.00', 5);

        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.nombre'],
            ['value_str' => 'Tienda Urbana', 'description' => 'Nombre comercial'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.razon_social'],
            ['value_str' => 'Tienda Urbana', 'description' => 'Razón social'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.cuit'],
            ['value_str' => '20-36436263-4', 'description' => 'CUIT'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.direccion'],
            ['value_str' => 'Belgrano 123', 'description' => 'Dirección'],
        );

        SucursalFiscalConfig::query()->create([
            'sucursal_id' => $branch->id,
            'modo_operacion' => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
            'entorno' => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
            'punto_venta' => 3,
            'facturacion_habilitada' => true,
            'requiere_receptor_en_todas' => false,
        ]);

        $this->actingAs($user)->post(route('caja.abrir'));
        $this->actingAs($user)->post(route('caja.carrito.agregar', $variant));
        $this->actingAs($user)->post(route('caja.pagos.agregar'));
        $this->actingAs($user)->post(route('caja.pagos.update', 0), [
            'tipo' => 'CONTADO',
            'monto' => '100.00',
        ]);

        $token = $this->app['session.store']->get('pos_confirm_token');

        $this->actingAs($user)
            ->post(route('caja.confirmar'), [
                'confirm_token' => $token,
                'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
            ])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('success');

        $sale = Venta::query()->with('comprobantePrincipal')->latest('id')->firstOrFail();
        $document = $sale->comprobantePrincipal;

        $this->assertSame(Venta::ESTADO_FISCAL_AUTORIZADO, $sale->estado_fiscal);
        $this->assertNotNull($document);
        $this->assertSame(99, $document?->doc_tipo_receptor);
        $this->assertSame('0', $document?->doc_nro_receptor);
        $this->assertSame('Consumidor Final', $document?->receptor_nombre);
        $this->assertSame('Consumidor Final', $document?->receptor_condicion_iva);
        $this->assertSame(5, data_get($document?->request_payload_json, 'wsfe_request.detail.CondicionIVAReceptorId'));
        $this->assertSame(5, data_get($document?->request_payload_json, 'receptor.condicion_iva_id'));
    }

    public function test_authorized_invoice_releases_number_reserved_by_previous_rejected_document(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();
        $variant = $this->createVariantFixture($branch, '100.00', 5);

        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.nombre'],
            ['value_str' => 'Tienda Urbana', 'description' => 'Nombre comercial'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.razon_social'],
            ['value_str' => 'Tienda Urbana', 'description' => 'Razón social'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.cuit'],
            ['value_str' => '20-36436263-4', 'description' => 'CUIT'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.direccion'],
            ['value_str' => 'Belgrano 123', 'description' => 'Dirección'],
        );

        SucursalFiscalConfig::query()->create([
            'sucursal_id' => $branch->id,
            'modo_operacion' => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
            'entorno' => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
            'punto_venta' => 3,
            'facturacion_habilitada' => true,
            'requiere_receptor_en_todas' => false,
        ]);

        $oldSale = Venta::query()->create([
            'sucursal_id' => $branch->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'medio_pago' => Venta::MEDIO_PAGO_EFECTIVO,
            'total' => '100.00',
            'numero_sucursal' => 1,
        ]);

        $oldDocument = VentaComprobante::query()->create([
            'venta_id' => $oldSale->id,
            'sucursal_id' => $branch->id,
            'modo_emision' => VentaComprobante::MODO_ELECTRONICA_ARCA,
            'estado' => VentaComprobante::ESTADO_RECHAZADO,
            'tipo_comprobante' => VentaComprobante::TIPO_FACTURA,
            'clase' => 'C',
            'codigo_arca' => 11,
            'punto_venta' => 3,
            'numero_comprobante' => 1,
            'fecha_emision' => now(),
            'moneda' => 'PES',
            'cotizacion_moneda' => '1.000000',
            'doc_tipo_receptor' => 99,
            'doc_nro_receptor' => '0',
            'receptor_nombre' => 'Consumidor Final',
            'receptor_condicion_iva' => 'Consumidor Final',
            'importe_neto' => '100.00',
            'importe_iva' => '0.00',
            'importe_otros_tributos' => '0.00',
            'importe_total' => '100.00',
            'resultado_arca' => 'R',
            'response_payload_json' => ['observations' => []],
            'request_payload_json' => ['point_of_sale' => 3],
        ]);

        $this->actingAs($user)->post(route('caja.abrir'));
        $this->actingAs($user)->post(route('caja.carrito.agregar', $variant));
        $this->actingAs($user)->post(route('caja.pagos.agregar'));
        $this->actingAs($user)->post(route('caja.pagos.update', 0), [
            'tipo' => 'CONTADO',
            'monto' => '100.00',
        ]);

        $token = $this->app['session.store']->get('pos_confirm_token');

        $this->actingAs($user)
            ->post(route('caja.confirmar'), [
                'confirm_token' => $token,
                'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
            ])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('success');

        $newDocument = VentaComprobante::query()
            ->whereKeyNot($oldDocument->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(VentaComprobante::ESTADO_AUTORIZADO, $newDocument->estado);
        $this->assertSame(3, $newDocument->punto_venta);
        $this->assertSame(11, $newDocument->codigo_arca);
        $this->assertSame(1, $newDocument->numero_comprobante);
        $this->assertNull($oldDocument->fresh()->numero_comprobante);
        $this->assertSame(VentaComprobante::ESTADO_RECHAZADO, $oldDocument->fresh()->estado);
    }

    public function test_electronic_invoice_uses_represented_cuit_from_arca_credentials_when_company_cuit_is_invalid(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();

        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.cuit'],
            ['value_str' => '306525412', 'description' => 'CUIT'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'fiscal.arca.represented_cuit'],
            ['value_str' => '20364362634', 'description' => 'CUIT representado para ARCA'],
        );

        SucursalFiscalConfig::query()->create([
            'sucursal_id' => $branch->id,
            'modo_operacion' => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
            'entorno' => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
            'punto_venta' => 3,
            'facturacion_habilitada' => true,
            'requiere_receptor_en_todas' => false,
        ]);

        $variant = $this->createVariantFixture($branch, '12100.00', 3);
        $this->actingAs($user)->post(route('caja.abrir'));
        $this->actingAs($user)->post(route('caja.carrito.agregar', $variant));
        $this->actingAs($user)->post(route('caja.pagos.agregar'));
        $this->actingAs($user)->post(route('caja.pagos.update', 0), [
            'tipo' => 'CONTADO',
            'monto' => '12100.00',
        ]);

        $token = $this->app['session.store']->get('pos_confirm_token');

        $this->actingAs($user)
            ->post(route('caja.confirmar'), [
                'confirm_token' => $token,
                'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
                'fiscal_receptor_doc_tipo' => 'DNI',
                'fiscal_receptor_doc_nro' => '30111222',
                'fiscal_receptor_nombre' => 'Ana Lopez',
                'fiscal_receptor_domicilio' => 'Belgrano 123',
            ])
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('success');

        $sale = Venta::query()->with('comprobantePrincipal')->latest('id')->firstOrFail();
        $ui = app(FiscalConfigManager::class)->branchUi($branch);
        $context = app(FiscalDocumentBuilder::class)->buildElectronicInvoiceContext($sale, $ui, [
            'fiscal_receptor_doc_tipo' => 'DNI',
            'fiscal_receptor_doc_nro' => '30111222',
            'fiscal_receptor_nombre' => 'Ana Lopez',
            'fiscal_receptor_domicilio' => 'Belgrano 123',
        ]);

        $this->assertSame('20364362634', $sale->empresa_cuit_snapshot);
        $this->assertSame(20364362634, data_get($context, 'wsfe.auth.cuit'));
    }

    public function test_closing_cash_register_clears_state_and_shows_summary(): void
    {
        [$user, $branch] = $this->createCashierWithBranch();

        $this->actingAs($user)->post(route('caja.abrir'));

        /** @var CajaSesion $session */
        $session = CajaSesion::query()->firstOrFail();

        Venta::query()->create([
            'sucursal_id' => $branch->id,
            'caja_sesion_id' => $session->id,
            'cajero_id' => $user->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'medio_pago' => Venta::MEDIO_PAGO_EFECTIVO,
            'total' => '245.50',
            'numero_sucursal' => 1,
        ]);

        $this->actingAs($user)
            ->withSession([
                'pos_cart' => ['1' => ['qty' => 1, 'precio' => '100.00']],
                'pos_payments' => [['tipo' => 'CONTADO', 'monto' => '100.00']],
            ])
            ->post(route('caja.cerrar'))
            ->assertRedirect(route('caja.pos'))
            ->assertSessionHas('success');

        $this->assertNotNull($session->fresh()->cerrada_en);
        $this->assertSame([], $this->app['session.store']->get('pos_cart', []));
        $this->assertSame([], $this->app['session.store']->get('pos_payments', []));

        $this->actingAs($user)
            ->get(route('caja.pos'))
            ->assertOk()
            ->assertSee('Cierre anterior: 1 venta(s) confirmada(s)');
    }

    protected function createCashierWithBranch(): array
    {
        $user = User::factory()->create();
        $branch = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'sucursal_id' => $branch->id,
        ]);

        return [$user, $branch];
    }

    protected function createVariantFixture(Sucursal $branch, string $price, int $stock): Variante
    {
        $category = Categoria::query()->create([
            'nombre' => 'Abrigos',
            'activa' => true,
        ]);
        $product = Producto::query()->create([
            'nombre' => 'Campera Nevada',
            'categoria_id' => $category->id,
            'activo' => true,
            'precio_base' => $price,
            'costo_base' => '60.00',
        ]);
        $variant = Variante::query()->create([
            'producto_id' => $product->id,
            'sku' => 'CAMP-NEV-42',
            'codigo_barras' => '7791234567890',
            'precio' => $price,
            'costo' => '60.00',
            'activo' => true,
        ]);

        StockSucursal::query()->create([
            'sucursal_id' => $branch->id,
            'variante_id' => $variant->id,
            'cantidad' => $stock,
        ]);

        return $variant;
    }
}
