<?php

namespace Tests\Feature\AdminPanel;

use App\Domain\Admin\Support\AdminPermissionCatalog;
use App\Domain\Catalogo\Models\Categoria;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Core\Models\AppSetting;
use App\Domain\Core\Models\Sucursal;
use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Domain\Ventas\Models\PlanCuotas;
use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Models\VentaItem;
use App\Domain\Ventas\Models\VentaPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_sales_list_detail_and_balances_render(): void
    {
        $user = User::factory()->create();
        $fixture = $this->createConfirmedSaleFixture($user);

        $this->actingAs($user)
            ->get(route('admin-panel.dashboard'))
            ->assertOk()
            ->assertSee('Bienvenido')
            ->assertSee('administrar catálogo, ventas y usuarios', false);

        $this->actingAs($user)
            ->get(route('admin-panel.ventas.index'))
            ->assertOk()
            ->assertSee($fixture['venta']->codigo_sucursal)
            ->assertSee('Credito')
            ->assertSee('data-label="Total"', false)
            ->assertSee(route('caja.ticket', $fixture['venta']).'?print=1');

        $this->actingAs($user)
            ->get(route('admin-panel.ventas.show', $fixture['venta']))
            ->assertOk()
            ->assertSee('Campera Nevada')
            ->assertSee('VISA')
            ->assertSee('Credito')
            ->assertSee('data-label="Subtotal"', false)
            ->assertSee(route('caja.ticket', $fixture['venta']).'?print=1');

        $this->actingAs($user)
            ->get(route('admin-panel.balances.index', [
                'from' => now()->format('Y-m-d'),
                'to' => now()->format('Y-m-d'),
                'vista' => 'ventas',
            ]))
            ->assertOk()
            ->assertSee('Total')
            ->assertSee('Casa Central');
    }

    public function test_settings_can_be_updated_for_a_branch(): void
    {
        $user = User::factory()->create();
        $branch = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin-panel.settings.index', ['sucursal' => $branch->id]))
            ->assertOk()
            ->assertSee('Permitir vender sin stock');

        $this->actingAs($user)
            ->put(route('admin-panel.settings.update'), [
                'sucursal_id' => $branch->id,
                'permitir_sin_stock' => '1',
                'permitir_cambiar_precio_venta' => '0',
            ])
            ->assertRedirect(route('admin-panel.settings.index', ['sucursal' => $branch->id]));

        $this->assertDatabaseHas('app_settings', [
            'key' => "ventas.sucursal.{$branch->id}.permitir_sin_stock",
            'value_bool' => true,
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => "ventas.sucursal.{$branch->id}.permitir_cambiar_precio_venta",
            'value_bool' => false,
        ]);
    }

    public function test_company_data_and_branch_crud_work(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('admin-panel.empresa.update'), [
                'nombre' => 'Tienda Urbana',
                'razon_social' => 'Tienda Urbana SRL',
                'cuit' => '30-12345678-9',
                'condicion_fiscal' => 'RESPONSABLE_INSCRIPTO',
                'direccion' => 'Av. Siempre Viva 742',
            ])
            ->assertRedirect(route('admin-panel.empresa.index', ['tab' => 'empresa']));

        $this->assertSame('Tienda Urbana', AppSetting::query()->where('key', 'empresa.nombre')->value('value_str'));
        $this->assertSame('RESPONSABLE_INSCRIPTO', AppSetting::query()->where('key', 'empresa.condicion_fiscal')->value('value_str'));

        $this->actingAs($user)
            ->post(route('admin-panel.sucursales.store'), [
                'nombre' => 'Sucursal Norte',
                'direccion' => 'Belgrano 123',
                'telefono' => '3515551212',
                'activa' => '1',
            ])
            ->assertRedirect(route('admin-panel.empresa.index', ['tab' => 'sucursales']));

        $branch = Sucursal::query()->where('nombre', 'Sucursal Norte')->firstOrFail();

        $this->actingAs($user)
            ->put(route('admin-panel.sucursales.update', $branch), [
                'nombre' => 'Sucursal Norte',
                'direccion' => 'Belgrano 456',
                'telefono' => '3510000000',
                'activa' => '1',
            ])
            ->assertRedirect(route('admin-panel.empresa.index', ['tab' => 'sucursales']));

        $this->actingAs($user)
            ->patch(route('admin-panel.sucursales.toggle', $branch))
            ->assertRedirect(route('admin-panel.empresa.index', ['tab' => 'sucursales']));

        $this->assertDatabaseHas('sucursales', [
            'id' => $branch->id,
            'direccion' => 'Belgrano 456',
            'telefono' => '3510000000',
            'activa' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin-panel.empresa.index', ['tab' => 'sucursales']))
            ->assertOk()
            ->assertSee('data-label="Teléfono"', false);
    }

    public function test_card_plans_can_be_created_updated_and_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin-panel.tarjetas.store'), [
                'tarjeta' => 'visa',
                'cuotas' => 6,
                'recargo_pct' => '12.50',
                'activo' => '1',
            ])
            ->assertRedirect(route('admin-panel.tarjetas.index'));

        $plan = PlanCuotas::query()->firstOrFail();

        $this->assertSame('VISA', $plan->tarjeta);

        $this->actingAs($user)
            ->put(route('admin-panel.tarjetas.update', $plan), [
                'tarjeta' => 'visa',
                'cuotas' => 9,
                'recargo_pct' => '18.00',
                'activo' => '0',
            ])
            ->assertRedirect(route('admin-panel.tarjetas.index'));

        $this->assertDatabaseHas('plan_cuotas', [
            'id' => $plan->id,
            'tarjeta' => 'VISA',
            'cuotas' => 9,
            'recargo_pct' => '18.00',
            'activo' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('admin-panel.tarjetas.destroy', $plan))
            ->assertRedirect(route('admin-panel.tarjetas.index'));

        $this->assertDatabaseMissing('plan_cuotas', [
            'id' => $plan->id,
        ]);
    }

    public function test_users_and_roles_can_be_managed_from_admin_panel(): void
    {
        $user = User::factory()->create();
        $branch = Sucursal::query()->create([
            'nombre' => 'Centro',
            'activa' => true,
        ]);
        $permissionIds = app(AdminPermissionCatalog::class)
            ->ensurePermissions()
            ->pluck('id')
            ->take(3)
            ->all();

        $this->actingAs($user)
            ->get(route('admin-panel.users.index', ['tab' => 'roles']))
            ->assertOk()
            ->assertSee('Crear rol')
            ->assertSee('responsive-stack-table', false);

        $this->actingAs($user)
            ->post(route('admin-panel.roles.store'), [
                'name' => 'encargado',
                'permission_ids' => $permissionIds,
            ])
            ->assertRedirect(route('admin-panel.users.index', ['tab' => 'roles']));

        $role = Role::query()->where('name', 'encargado')->firstOrFail();

        $this->assertCount(3, $role->permissions);

        $this->actingAs($user)
            ->get(route('admin-panel.users.index', ['tab' => 'roles']))
            ->assertOk()
            ->assertSee('data-label="Permisos"', false);

        $this->actingAs($user)
            ->post(route('admin-panel.users.store'), [
                'username' => 'operador',
                'first_name' => 'Micaela',
                'last_name' => 'Perez',
                'email' => 'operador@example.com',
                'password' => 'clave123',
                'password_confirmation' => 'clave123',
                'is_active' => '1',
                'sucursal_id' => $branch->id,
                'role_ids' => [$role->id],
            ])
            ->assertRedirect(route('admin-panel.users.index', ['tab' => 'usuarios']));

        $managedUser = User::query()->where('username', 'operador')->firstOrFail();

        $this->assertTrue($managedUser->hasRole('encargado'));
        $this->assertSame($branch->id, $managedUser->panelProfile?->sucursal_id);

        $this->actingAs($user)
            ->put(route('admin-panel.users.update', $managedUser), [
                'username' => 'operador',
                'first_name' => 'Micaela',
                'last_name' => 'Lopez',
                'email' => 'operador@example.com',
                'is_active' => '1',
                'sucursal_id' => $branch->id,
                'role_ids' => [$role->id],
            ])
            ->assertRedirect(route('admin-panel.users.index', ['tab' => 'usuarios']));

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'last_name' => 'Lopez',
        ]);

        $this->actingAs($user)
            ->put(route('admin-panel.users.password', $managedUser), [
                'password' => 'nuevo123',
                'password_confirmation' => 'nuevo123',
            ])
            ->assertRedirect(route('admin-panel.users.index', ['tab' => 'usuarios']));

        $this->assertTrue(Hash::check('nuevo123', $managedUser->fresh()->password));

        $this->actingAs($user)
            ->patch(route('admin-panel.users.toggle', $managedUser))
            ->assertRedirect(route('admin-panel.users.index', ['tab' => 'usuarios']));

        $this->assertFalse($managedUser->fresh()->is_active);

        $this->actingAs($user)
            ->delete(route('admin-panel.roles.destroy', $role))
            ->assertRedirect(route('admin-panel.users.index', ['tab' => 'roles']))
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->put(route('admin-panel.users.update', $managedUser), [
                'username' => 'operador',
                'first_name' => 'Micaela',
                'last_name' => 'Lopez',
                'email' => 'operador@example.com',
                'is_active' => '1',
                'sucursal_id' => $branch->id,
                'role_ids' => [],
            ])
            ->assertRedirect(route('admin-panel.users.index', ['tab' => 'usuarios']));

        $this->actingAs($user)
            ->delete(route('admin-panel.roles.destroy', $role))
            ->assertRedirect(route('admin-panel.users.index', ['tab' => 'roles']));

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    protected function createConfirmedSaleFixture(User $user): array
    {
        $branch = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);
        $category = Categoria::query()->create([
            'nombre' => 'Abrigos',
            'activa' => true,
        ]);
        $product = Producto::query()->create([
            'nombre' => 'Campera Nevada',
            'categoria_id' => $category->id,
            'activo' => true,
            'precio_base' => '11000.00',
            'costo_base' => '7000.00',
        ]);
        $variant = Variante::query()->create([
            'producto_id' => $product->id,
            'sku' => 'CAMP-NEV-42',
            'precio' => '11000.00',
            'costo' => '7000.00',
            'activo' => true,
        ]);
        $client = Cliente::query()->create([
            'dni' => '30123456',
            'nombre' => 'Lucia',
            'apellido' => 'Fernandez',
            'activo' => true,
        ]);
        CuentaCorriente::query()->create([
            'cliente_id' => $client->id,
            'activa' => true,
        ]);
        $plan = PlanCuotas::query()->create([
            'tarjeta' => 'VISA',
            'cuotas' => 3,
            'recargo_pct' => '10.00',
            'activo' => true,
        ]);
        $sale = Venta::query()->create([
            'sucursal_id' => $branch->id,
            'cajero_id' => $user->id,
            'cliente_id' => $client->id,
            'fecha' => now(),
            'estado' => Venta::ESTADO_CONFIRMADA,
            'medio_pago' => Venta::MEDIO_PAGO_CREDITO,
            'total' => '12100.00',
            'numero_sucursal' => 1,
        ]);
        VentaItem::query()->create([
            'venta_id' => $sale->id,
            'variante_id' => $variant->id,
            'cantidad' => 1,
            'precio_unitario' => '11000.00',
            'iva_alicuota_pct' => '21.00',
        ]);
        VentaPago::query()->create([
            'venta_id' => $sale->id,
            'tipo' => VentaPago::TIPO_CREDITO,
            'monto' => '11000.00',
            'cuotas' => 3,
            'recargo_pct' => '10.00',
            'recargo_monto' => '1100.00',
            'plan_id' => $plan->id,
            'referencia' => 'VISA',
        ]);

        return [
            'branch' => $branch,
            'sale' => $sale,
            'venta' => $sale,
        ];
    }
}
