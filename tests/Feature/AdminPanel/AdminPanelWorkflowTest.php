<?php

namespace Tests\Feature\AdminPanel;

use App\Domain\Admin\Support\AdminPermissionCatalog;
use App\Domain\Catalogo\Models\Categoria;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Core\Models\AppSetting;
use App\Domain\Core\Models\Sucursal;
use App\Domain\Fiscal\Models\ArcaCaeaComprobante;
use App\Domain\Fiscal\Models\ArcaCaeaPeriodo;
use App\Domain\Fiscal\Models\SucursalFiscalConfig;
use App\Domain\Fiscal\Models\VentaComprobante;
use App\Domain\Fiscal\Support\ArcaCredentialManager;
use App\Domain\Fiscal\Support\ArcaHomologationProbe;
use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Domain\Ventas\Models\PlanCuotas;
use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Models\VentaItem;
use App\Domain\Ventas\Models\VentaPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
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
            ->assertSee('Permitir vender sin stock')
            ->assertSee('Facturación electrónica');

        $this->actingAs($user)
            ->get(route('admin-panel.settings.index', ['sucursal' => $branch->id, 'tab' => 'facturacion']))
            ->assertOk()
            ->assertSee('Punto de venta fiscal')
            ->assertSee('Domicilio fiscal de emision');

        AppSetting::query()->updateOrCreate([
            'key' => 'empresa.razon_social',
        ], [
            'key' => 'empresa.razon_social',
            'value_str' => 'Tienda Urbana SRL',
            'description' => 'Razón social',
        ]);
        AppSetting::query()->updateOrCreate([
            'key' => 'empresa.cuit',
        ], [
            'key' => 'empresa.cuit',
            'value_str' => '30-12345678-9',
            'description' => 'CUIT',
        ]);
        AppSetting::query()->updateOrCreate([
            'key' => 'empresa.direccion',
        ], [
            'key' => 'empresa.direccion',
            'value_str' => 'Av. Siempre Viva 742',
            'description' => 'Dirección',
        ]);

        $this->actingAs($user)
            ->put(route('admin-panel.settings.update'), [
                'sucursal_id' => $branch->id,
                'tab' => 'ventas',
                'permitir_sin_stock' => '1',
                'permitir_cambiar_precio_venta' => '0',
            ])
            ->assertRedirect(route('admin-panel.settings.index', ['sucursal' => $branch->id, 'tab' => 'ventas']));

        $this->actingAs($user)
            ->put(route('admin-panel.settings.update'), [
                'sucursal_id' => $branch->id,
                'tab' => 'facturacion',
                'fiscal_modo_operacion' => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
                'fiscal_entorno' => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
                'fiscal_punto_venta' => '11',
                'fiscal_facturacion_habilitada' => '1',
                'fiscal_requiere_receptor_en_todas' => '1',
                'fiscal_domicilio_fiscal_emision' => 'Av. Fiscal 123',
            ])
            ->assertRedirect(route('admin-panel.settings.index', ['sucursal' => $branch->id, 'tab' => 'facturacion']));

        $this->assertDatabaseHas('app_settings', [
            'key' => "ventas.sucursal.{$branch->id}.permitir_sin_stock",
            'value_bool' => true,
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => "ventas.sucursal.{$branch->id}.permitir_cambiar_precio_venta",
            'value_bool' => false,
        ]);
        $this->assertDatabaseHas('sucursal_fiscal_configs', [
            'sucursal_id' => $branch->id,
            'modo_operacion' => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
            'entorno' => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
            'punto_venta' => 11,
            'facturacion_habilitada' => true,
            'requiere_receptor_en_todas' => true,
            'domicilio_fiscal_emision' => 'Av. Fiscal 123',
        ]);
    }

    public function test_facturacion_electronica_can_list_authorized_documents_with_cae(): void
    {
        $user = User::factory()->create();
        $fixture = $this->createConfirmedSaleFixture($user);

        $document = VentaComprobante::query()->create([
            'venta_id' => $fixture['venta']->id,
            'sucursal_id' => $fixture['branch']->id,
            'modo_emision' => VentaComprobante::MODO_ELECTRONICA_ARCA,
            'estado' => VentaComprobante::ESTADO_AUTORIZADO,
            'tipo_comprobante' => VentaComprobante::TIPO_FACTURA,
            'clase' => 'B',
            'codigo_arca' => 6,
            'punto_venta' => 3,
            'numero_comprobante' => 1,
            'fecha_emision' => now(),
            'doc_tipo_receptor' => 96,
            'doc_nro_receptor' => '30123456',
            'receptor_nombre' => 'Lucia Fernandez',
            'importe_neto' => '10000.00',
            'importe_iva' => '2100.00',
            'importe_otros_tributos' => '0.00',
            'importe_total' => '12100.00',
            'cae' => '99990000123456',
            'cae_vto' => now()->addDays(10)->toDateString(),
            'qr_payload_json' => ['ver' => 1],
            'qr_url' => 'https://www.arca.gob.ar/fe/qr/?p=fake',
            'emitido_en' => now(),
        ]);

        $fixture['venta']->update([
            'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
            'estado_fiscal' => Venta::ESTADO_FISCAL_AUTORIZADO,
            'tiene_comprobante_fiscal' => true,
            'venta_comprobante_principal_id' => $document->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin-panel.settings.index', [
                'tab' => 'facturacion',
                'facturacion_tab' => 'comprobantes',
                'sucursal' => $fixture['branch']->id,
            ]))
            ->assertOk()
            ->assertSee('Comprobantes autorizados con CAE')
            ->assertSee('99990000123456')
            ->assertSee('0003-00000001')
            ->assertSee(route('fiscal.comprobantes.show', $document).'?print=1')
            ->assertSee(route('admin-panel.ventas.show', $fixture['venta']));
    }

    public function test_facturacion_electronica_can_list_caea_periods(): void
    {
        $user = User::factory()->create();
        $branch = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);

        ArcaCaeaPeriodo::query()->create([
            'entorno' => ArcaCaeaPeriodo::ENTORNO_HOMOLOGACION,
            'cuit_representada' => '20364362634',
            'periodo' => 202604,
            'orden' => 1,
            'caea' => '12345678901234',
            'estado_solicitud' => ArcaCaeaPeriodo::ESTADO_SOLICITUD_AUTORIZADO,
            'estado_informacion' => ArcaCaeaPeriodo::ESTADO_INFORMACION_PENDIENTE,
            'vigente_desde' => '2026-04-01',
            'vigente_hasta' => '2026-04-15',
            'fecha_tope_informar' => '2026-04-23',
            'comprobantes_informados' => 0,
            'ultimo_synced_at' => now(),
        ]);

        ArcaCaeaComprobante::query()->create([
            'arca_caea_periodo_id' => ArcaCaeaPeriodo::query()->firstOrFail()->id,
            'sucursal_id' => $branch->id,
            'punto_venta' => 3,
            'codigo_arca' => 11,
            'numero_comprobante' => 27,
            'fecha_emision' => '2026-04-05',
            'receptor_nombre' => 'Ana Lopez',
            'doc_nro_receptor' => '30111222',
            'importe_total' => '20000.00',
            'estado_rendicion' => ArcaCaeaComprobante::ESTADO_RENDICION_INFORMADO,
            'informado_en' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin-panel.settings.index', [
                'tab' => 'facturacion',
                'facturacion_tab' => 'caea',
                'sucursal' => $branch->id,
            ]))
            ->assertOk()
            ->assertSee('Períodos CAEA autorizados e informables')
            ->assertSee('12345678901234')
            ->assertSee('04/2026')
            ->assertSee('1ra quincena')
            ->assertSee('Pendiente')
            ->assertSee('20364362634')
            ->assertSee('0003-00000027')
            ->assertSee('Informado')
            ->assertSee('Ana Lopez');
    }

    public function test_arca_credentials_can_be_generated_from_admin_settings(): void
    {
        $user = User::factory()->create();
        $branch = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin-panel.settings.index', ['sucursal' => $branch->id, 'tab' => 'credenciales']))
            ->assertOk()
            ->assertSee('Credenciales ARCA')
            ->assertSee('Generar key + CSR');

        $this->actingAs($user)
            ->post(route('admin-panel.settings.arca.generate-csr'), [
                'sucursal_id' => $branch->id,
                'arca_represented_cuit' => '20-36436263-4',
                'arca_alias' => 'tiendaropahomo',
                'arca_organization' => 'Tienda Urbana SRL',
                'arca_common_name' => 'tienda_ropa_laravel',
            ])
            ->assertRedirect(route('admin-panel.settings.index', ['tab' => 'credenciales', 'sucursal' => $branch->id]));

        $privateKeyPath = (string) AppSetting::query()->where('key', 'fiscal.arca.private_key_path')->value('value_str');
        $csrPath = (string) AppSetting::query()->where('key', 'fiscal.arca.csr_path')->value('value_str');

        $this->assertDatabaseHas('app_settings', [
            'key' => 'fiscal.arca.represented_cuit',
            'value_str' => '20364362634',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => 'fiscal.arca.alias',
            'value_str' => 'tiendaropahomo',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => 'fiscal.arca.certificate_path',
            'value_str' => '',
        ]);
        $this->assertNotSame('', $privateKeyPath);
        $this->assertNotSame('', $csrPath);
        $this->assertTrue(File::exists(base_path($privateKeyPath)));
        $this->assertTrue(File::exists(base_path($csrPath)));

        $this->actingAs($user)
            ->get(route('admin-panel.settings.index', ['sucursal' => $branch->id, 'tab' => 'credenciales']))
            ->assertOk()
            ->assertSee('CSR lista para copiar')
            ->assertSee('BEGIN CERTIFICATE REQUEST')
            ->assertSee('Copiar CSR');
    }

    public function test_arca_certificate_can_be_uploaded_validated_and_probed_from_admin_settings(): void
    {
        $user = User::factory()->create();
        $branch = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);

        $manager = app(ArcaCredentialManager::class);
        $manager->generateKeyAndCsr(
            '20364362634',
            'tiendaropahomo',
            'Tienda Urbana SRL',
            'tienda_ropa_laravel',
        );

        $certificatePem = $this->issueSelfSignedCertificatePem(
            (string) AppSetting::query()->where('key', 'fiscal.arca.csr_path')->value('value_str'),
            (string) AppSetting::query()->where('key', 'fiscal.arca.private_key_path')->value('value_str'),
        );

        $this->actingAs($user)
            ->post(route('admin-panel.settings.arca.upload-certificate'), [
                'sucursal_id' => $branch->id,
                'arca_certificate_pem' => $certificatePem,
            ])
            ->assertRedirect(route('admin-panel.settings.index', ['tab' => 'credenciales', 'sucursal' => $branch->id]))
            ->assertSessionHas('arca_validation', fn (array $validation) => ($validation['ok'] ?? false) === true);

        $certificatePath = (string) AppSetting::query()->where('key', 'fiscal.arca.certificate_path')->value('value_str');
        $this->assertNotSame('', $certificatePath);
        $this->assertTrue(File::exists(base_path($certificatePath)));

        $this->actingAs($user)
            ->post(route('admin-panel.settings.arca.validate-credentials'), [
                'sucursal_id' => $branch->id,
            ])
            ->assertRedirect(route('admin-panel.settings.index', ['tab' => 'credenciales', 'sucursal' => $branch->id]))
            ->assertSessionHas('arca_validation', fn (array $validation) => ($validation['ok'] ?? false) === true);

        $probeMock = \Mockery::mock(ArcaHomologationProbe::class);
        $probeMock->shouldReceive('probeBranch')
            ->once()
            ->andReturnUsing(function ($branch): array {
                return [
                    'branch' => ['id' => $branch->id, 'nombre' => $branch->nombre],
                    'environment' => 'HOMOLOGACION',
                    'point_of_sale' => 11,
                    'receipt_class' => 'B',
                    'receipt_code' => 6,
                    'readiness' => ['ready' => true, 'issues' => []],
                    'wsfe_dummy' => [
                        'app_server' => 'OK',
                        'db_server' => 'OK',
                        'auth_server' => 'OK',
                    ],
                    'wsaa' => [
                        'expiration_time' => now()->addHours(12)->toIso8601String(),
                    ],
                    'last_authorized' => [
                        'numero' => 123,
                    ],
                ];
            });
        $this->app->instance(ArcaHomologationProbe::class, $probeMock);

        $this->actingAs($user)
            ->post(route('admin-panel.settings.arca.probe'), [
                'sucursal_id' => $branch->id,
            ])
            ->assertRedirect(route('admin-panel.settings.index', ['tab' => 'credenciales', 'sucursal' => $branch->id]))
            ->assertSessionHas('arca_probe', fn (array $probe) => ($probe['environment'] ?? null) === 'HOMOLOGACION');
    }

    public function test_arca_credentials_tab_is_available_even_without_branches(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin-panel.settings.index', ['tab' => 'credenciales']))
            ->assertOk()
            ->assertSee('Credenciales ARCA')
            ->assertSee('Generar key + CSR')
            ->assertSee('solo la prueba de homologación requiere una sucursal', false);
    }

    public function test_arca_alias_rejects_non_alphanumeric_characters(): void
    {
        $user = User::factory()->create();
        $branch = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);

        $this->actingAs($user)
            ->from(route('admin-panel.settings.index', ['tab' => 'credenciales', 'sucursal' => $branch->id]))
            ->post(route('admin-panel.settings.arca.generate-csr'), [
                'sucursal_id' => $branch->id,
                'arca_represented_cuit' => '20364362634',
                'arca_alias' => 'tienda-ropa-homo',
                'arca_organization' => 'EmpresaAR',
                'arca_common_name' => 'tienda_ropa',
            ])
            ->assertRedirect(route('admin-panel.settings.index', ['tab' => 'credenciales', 'sucursal' => $branch->id]))
            ->assertSessionHasErrors([
                'arca_alias' => 'El alias DN solo puede contener letras y números, sin espacios ni símbolos.',
            ]);
    }

    public function test_sales_views_expose_printable_fiscal_document_when_present(): void
    {
        $user = User::factory()->create();
        $fixture = $this->createConfirmedSaleFixture($user);
        $document = VentaComprobante::query()->create([
            'venta_id' => $fixture['venta']->id,
            'sucursal_id' => $fixture['branch']->id,
            'modo_emision' => VentaComprobante::MODO_ELECTRONICA_ARCA,
            'estado' => VentaComprobante::ESTADO_AUTORIZADO,
            'tipo_comprobante' => VentaComprobante::TIPO_FACTURA,
            'clase' => 'B',
            'codigo_arca' => 6,
            'punto_venta' => 3,
            'numero_comprobante' => 1,
            'fecha_emision' => now(),
            'doc_tipo_receptor' => 96,
            'doc_nro_receptor' => '30123456',
            'receptor_nombre' => 'Lucia Fernandez',
            'importe_neto' => '10000.00',
            'importe_iva' => '2100.00',
            'importe_otros_tributos' => '0.00',
            'importe_total' => '12100.00',
            'cae' => '99990000123456',
            'cae_vto' => now()->addDays(10)->toDateString(),
            'qr_payload_json' => ['ver' => 1],
            'qr_url' => 'https://www.arca.gob.ar/fe/qr/?p=fake',
            'emitido_en' => now(),
        ]);
        $fixture['venta']->update([
            'accion_fiscal' => Venta::ACCION_FISCAL_FACTURA_ELECTRONICA,
            'estado_fiscal' => Venta::ESTADO_FISCAL_AUTORIZADO,
            'tiene_comprobante_fiscal' => true,
            'venta_comprobante_principal_id' => $document->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin-panel.ventas.index'))
            ->assertOk()
            ->assertSee(route('fiscal.comprobantes.show', $document).'?print=1');

        $this->actingAs($user)
            ->get(route('admin-panel.ventas.show', $fixture['venta']->fresh()))
            ->assertOk()
            ->assertSee(route('fiscal.comprobantes.show', $document).'?print=1')
            ->assertSee('99990000123456');
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

    protected function issueSelfSignedCertificatePem(string $csrPath, string $privateKeyPath): string
    {
        $opensslConfig = realpath('E:\\Dev\\Tools\\laravel-runtime\\php\\extras\\ssl\\openssl.cnf');

        if ($opensslConfig) {
            putenv("OPENSSL_CONF={$opensslConfig}");
        }

        $csrPem = (string) File::get(base_path($csrPath));
        $privateKey = openssl_pkey_get_private((string) File::get(base_path($privateKeyPath)));

        $this->assertNotFalse($privateKey);

        $options = $opensslConfig ? ['config' => $opensslConfig, 'digest_alg' => 'sha256'] : ['digest_alg' => 'sha256'];
        $certificate = openssl_csr_sign($csrPem, null, $privateKey, 365, $options);

        $this->assertNotFalse($certificate);

        $certificatePem = '';
        $exported = openssl_x509_export($certificate, $certificatePem);

        $this->assertTrue($exported);

        return $certificatePem;
    }
}
