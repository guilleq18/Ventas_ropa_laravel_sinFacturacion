<?php

namespace Tests\Feature\CuentasCorrientes;

use App\Domain\Core\Models\Sucursal;
use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente;
use App\Domain\CuentasCorrientes\Models\PagoCuentaCorriente;
use App\Domain\CuentasCorrientes\Models\PagoCuentaCorrienteAplicacion;
use App\Domain\Ventas\Models\Venta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuentaCorrienteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_account_and_client(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cuentas-corrientes.store'), [
                'dni' => '30111222',
                'nombre' => 'Ana',
                'apellido' => 'Lopez',
                'telefono' => '11223344',
                'direccion' => 'Mitre 123',
                'fecha_nacimiento' => '1990-04-10',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clientes', [
            'dni' => '30111222',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
        ]);
        $this->assertDatabaseHas('cuentas_corrientes', [
            'cliente_id' => Cliente::query()->where('dni', '30111222')->value('id'),
            'activa' => true,
        ]);
    }

    public function test_existing_client_without_account_gets_new_account_without_duplication(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::query()->create([
            'dni' => '20999888',
            'nombre' => 'Mario',
            'apellido' => 'Sosa',
            'activo' => true,
        ]);

        $this->actingAs($user)
            ->post(route('cuentas-corrientes.store'), [
                'dni' => '20999888',
                'nombre' => 'Mario',
                'apellido' => 'Sosa',
                'telefono' => '',
                'direccion' => '',
                'fecha_nacimiento' => '',
            ])
            ->assertRedirect();

        $this->assertSame(1, Cliente::query()->where('dni', '20999888')->count());
        $this->assertDatabaseHas('cuentas_corrientes', [
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_duplicate_account_is_rejected(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::query()->create([
            'dni' => '18999111',
            'nombre' => 'Lucia',
            'apellido' => 'Mendez',
            'activo' => true,
        ]);
        CuentaCorriente::query()->create([
            'cliente_id' => $cliente->id,
            'activa' => true,
        ]);

        $this->actingAs($user)
            ->from(route('cuentas-corrientes.index'))
            ->post(route('cuentas-corrientes.store'), [
                'dni' => '18999111',
                'nombre' => 'Lucia',
                'apellido' => 'Mendez',
                'telefono' => '',
                'direccion' => '',
                'fecha_nacimiento' => '',
            ])
            ->assertRedirect(route('cuentas-corrientes.index'))
            ->assertSessionHasErrors('dni');
    }

    public function test_index_filters_by_search_and_state(): void
    {
        $user = User::factory()->create();

        $clienteActivo = Cliente::query()->create([
            'dni' => '1111',
            'nombre' => 'Julia',
            'apellido' => 'Rios',
            'activo' => true,
        ]);
        $clienteInactivo = Cliente::query()->create([
            'dni' => '2222',
            'nombre' => 'Pedro',
            'apellido' => 'Luna',
            'activo' => false,
        ]);

        CuentaCorriente::query()->create([
            'cliente_id' => $clienteActivo->id,
            'activa' => true,
        ]);
        CuentaCorriente::query()->create([
            'cliente_id' => $clienteInactivo->id,
            'activa' => false,
        ]);

        $this->actingAs($user)
            ->get(route('cuentas-corrientes.index', ['q' => 'Julia', 'activa' => '1']))
            ->assertOk()
            ->assertSee('Rios, Julia')
            ->assertDontSee('Luna, Pedro');
    }

    public function test_dni_lookup_reports_existing_client_without_account(): void
    {
        $user = User::factory()->create();
        Cliente::query()->create([
            'dni' => '20444555',
            'nombre' => 'Sofia',
            'apellido' => 'Paz',
            'telefono' => '1130303030',
            'direccion' => 'Belgrano 456',
            'fecha_nacimiento' => '1994-06-11',
            'activo' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('cuentas-corrientes.lookup-dni', ['dni' => '20444555']))
            ->assertOk()
            ->assertJson([
                'status' => 'existing_client',
                'dni' => '20444555',
                'cliente' => [
                    'dni' => '20444555',
                    'nombre' => 'Sofia',
                    'apellido' => 'Paz',
                ],
                'cuenta_corriente' => null,
            ]);
    }

    public function test_dni_lookup_reports_duplicate_account_before_submit(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::query()->create([
            'dni' => '18999111',
            'nombre' => 'Lucia',
            'apellido' => 'Mendez',
            'activo' => true,
        ]);
        $cuenta = CuentaCorriente::query()->create([
            'cliente_id' => $cliente->id,
            'activa' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('cuentas-corrientes.lookup-dni', ['dni' => '18999111']))
            ->assertOk()
            ->assertJson([
                'status' => 'duplicate_account',
                'dni' => '18999111',
                'cliente' => [
                    'dni' => '18999111',
                    'nombre' => 'Lucia',
                    'apellido' => 'Mendez',
                ],
                'cuenta_corriente' => [
                    'id' => $cuenta->id,
                    'activa' => true,
                    'show_url' => route('cuentas-corrientes.show', $cuenta),
                ],
            ]);
    }

    public function test_detail_allows_registering_payments_and_toggling_statuses(): void
    {
        $user = User::factory()->create();
        $sucursal = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);
        $cliente = Cliente::query()->create([
            'dni' => '33444555',
            'nombre' => 'Laura',
            'apellido' => 'Gomez',
            'activo' => true,
        ]);
        $cuenta = CuentaCorriente::query()->create([
            'cliente_id' => $cliente->id,
            'activa' => true,
        ]);
        $venta = Venta::query()->create([
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'medio_pago' => Venta::MEDIO_PAGO_CUENTA_CORRIENTE,
            'total' => '150.00',
            'numero_sucursal' => 15,
        ]);

        $debitMovement = MovimientoCuentaCorriente::query()->create([
            'cuenta_id' => $cuenta->id,
            'tipo' => MovimientoCuentaCorriente::TIPO_DEBITO,
            'monto' => '150.00',
            'fecha' => now()->subDays(45),
            'venta_id' => $venta->id,
            'referencia' => 'Venta inicial',
        ]);

        $this->actingAs($user)
            ->get(route('cuentas-corrientes.payments.create', $cuenta))
            ->assertOk()
            ->assertSee('Registrar pago')
            ->assertSee($venta->codigo_sucursal)
            ->assertSee('data-label="Debe"', false)
            ->assertSee('cc-sale-status', false);

        $this->actingAs($user)
            ->post(route('cuentas-corrientes.payments.store', $cuenta), [
                'monto' => '40.00',
                'referencia' => 'Transferencia',
                'observacion' => 'Pago parcial',
                'ventas' => [$venta->id],
            ])
            ->assertRedirect(route('cuentas-corrientes.payments.create', $cuenta));

        $creditMovement = MovimientoCuentaCorriente::query()
            ->where('cuenta_id', $cuenta->id)
            ->where('tipo', MovimientoCuentaCorriente::TIPO_CREDITO)
            ->first();

        $this->assertNotNull($creditMovement);
        $this->assertDatabaseHas('movimientos_cuenta_corriente', [
            'cuenta_id' => $cuenta->id,
            'tipo' => MovimientoCuentaCorriente::TIPO_CREDITO,
            'monto' => '40.00',
            'referencia' => 'Transferencia',
        ]);
        $this->assertDatabaseHas('pagos_cuenta_corriente', [
            'cuenta_id' => $cuenta->id,
            'movimiento_credito_id' => $creditMovement?->id,
        ]);
        $this->assertDatabaseHas('pago_cuenta_corriente_aplicaciones', [
            'movimiento_debito_id' => $debitMovement->id,
            'monto_aplicado' => '40.00',
        ]);
        $this->assertSame('110.00', $cuenta->fresh()->saldo());

        $this->actingAs($user)
            ->get(route('cuentas-corrientes.payments.create', $cuenta))
            ->assertOk()
            ->assertSee('Pago parcial')
            ->assertSee('Monto original')
            ->assertSee('Debe')
            ->assertSee('$150,00')
            ->assertSee('$110,00');

        $this->actingAs($user)
            ->patch(route('cuentas-corrientes.toggle', $cuenta))
            ->assertRedirect(route('cuentas-corrientes.show', $cuenta));
        $this->assertFalse($cuenta->fresh()->activa);

        $this->actingAs($user)
            ->patch(route('cuentas-corrientes.clientes.toggle', $cliente))
            ->assertRedirect(route('cuentas-corrientes.show', $cuenta));
        $this->assertFalse($cliente->fresh()->activo);
    }

    public function test_payment_amount_cannot_exceed_selected_sales_pending_balance(): void
    {
        $user = User::factory()->create();
        $sucursal = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);
        $cliente = Cliente::query()->create([
            'dni' => '30333444',
            'nombre' => 'Rocio',
            'apellido' => 'Diaz',
            'activo' => true,
        ]);
        $cuenta = CuentaCorriente::query()->create([
            'cliente_id' => $cliente->id,
            'activa' => true,
        ]);
        $venta = Venta::query()->create([
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'medio_pago' => Venta::MEDIO_PAGO_CUENTA_CORRIENTE,
            'total' => '90.00',
            'numero_sucursal' => 16,
            'fecha' => now()->subDays(20),
        ]);

        MovimientoCuentaCorriente::query()->create([
            'cuenta_id' => $cuenta->id,
            'tipo' => MovimientoCuentaCorriente::TIPO_DEBITO,
            'monto' => '90.00',
            'fecha' => now()->subDays(20),
            'venta_id' => $venta->id,
            'referencia' => 'Venta pendiente',
        ]);

        $this->actingAs($user)
            ->from(route('cuentas-corrientes.payments.create', $cuenta))
            ->post(route('cuentas-corrientes.payments.store', $cuenta), [
                'monto' => '120.00',
                'referencia' => 'Pago excedido',
                'observacion' => '',
                'ventas' => [$venta->id],
            ])
            ->assertRedirect(route('cuentas-corrientes.payments.create', $cuenta))
            ->assertSessionHasErrors('monto');

        $this->assertDatabaseCount('pagos_cuenta_corriente', 0);
        $this->assertDatabaseCount('pago_cuenta_corriente_aplicaciones', 0);
    }

    public function test_index_and_detail_show_alerts_for_sales_overdue_more_than_thirty_days(): void
    {
        $user = User::factory()->create();
        $sucursal = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);
        $cliente = Cliente::query()->create([
            'dni' => '35555111',
            'nombre' => 'Elena',
            'apellido' => 'Ruiz',
            'activo' => true,
        ]);
        $cuenta = CuentaCorriente::query()->create([
            'cliente_id' => $cliente->id,
            'activa' => true,
        ]);
        $ventaVencida = Venta::query()->create([
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'medio_pago' => Venta::MEDIO_PAGO_CUENTA_CORRIENTE,
            'total' => '150.00',
            'numero_sucursal' => 17,
            'fecha' => now()->subDays(45),
        ]);
        $ventaReciente = Venta::query()->create([
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'medio_pago' => Venta::MEDIO_PAGO_CUENTA_CORRIENTE,
            'total' => '60.00',
            'numero_sucursal' => 18,
            'fecha' => now()->subDays(8),
        ]);

        $debitoVencido = MovimientoCuentaCorriente::query()->create([
            'cuenta_id' => $cuenta->id,
            'tipo' => MovimientoCuentaCorriente::TIPO_DEBITO,
            'monto' => '150.00',
            'fecha' => now()->subDays(45),
            'venta_id' => $ventaVencida->id,
            'referencia' => 'Venta vencida',
        ]);
        MovimientoCuentaCorriente::query()->create([
            'cuenta_id' => $cuenta->id,
            'tipo' => MovimientoCuentaCorriente::TIPO_DEBITO,
            'monto' => '60.00',
            'fecha' => now()->subDays(8),
            'venta_id' => $ventaReciente->id,
            'referencia' => 'Venta reciente',
        ]);
        $creditMovement = MovimientoCuentaCorriente::query()->create([
            'cuenta_id' => $cuenta->id,
            'tipo' => MovimientoCuentaCorriente::TIPO_CREDITO,
            'monto' => '40.00',
            'fecha' => now()->subDays(2),
            'referencia' => 'Pago parcial',
        ]);
        $payment = PagoCuentaCorriente::query()->create([
            'cuenta_id' => $cuenta->id,
            'movimiento_credito_id' => $creditMovement->id,
        ]);
        PagoCuentaCorrienteAplicacion::query()->create([
            'pago_cuenta_corriente_id' => $payment->id,
            'movimiento_debito_id' => $debitoVencido->id,
            'monto_aplicado' => '40.00',
        ]);

        $this->actingAs($user)
            ->get(route('cuentas-corrientes.index'))
            ->assertOk()
            ->assertSee('Alerta de mora +30 dias')
            ->assertSee(route('cuentas-corrientes.payments.create', $cuenta))
            ->assertSee('data-label="Saldo"', false)
            ->assertSee('$110,00');

        $this->actingAs($user)
            ->get(route('cuentas-corrientes.show', $cuenta))
            ->assertOk()
            ->assertSee('Alerta de mora +30 dias')
            ->assertSee(route('cuentas-corrientes.payments.create', $cuenta))
            ->assertSee('data-label="Monto"', false)
            ->assertSee('$110,00');
    }
}
