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
