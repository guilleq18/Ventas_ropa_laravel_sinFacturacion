<?php

namespace Tests\Feature\Caja;

use App\Domain\Catalogo\Models\Categoria;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Core\Models\AppSetting;
use App\Domain\Core\Models\Sucursal;
use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Models\VentaItem;
use App\Domain\Ventas\Models\VentaPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_reprints_historic_sale_with_snapshot_company_and_fiscal_data(): void
    {
        $cashier = User::factory()->create([
            'username' => 'cajero.ticket',
            'first_name' => 'Mica',
            'last_name' => 'Rojas',
        ]);
        $branch = Sucursal::query()->create([
            'nombre' => 'Sucursal Ticket',
            'activa' => true,
        ]);
        $category = Categoria::query()->create([
            'nombre' => 'Abrigos',
            'activa' => true,
        ]);
        $product = Producto::query()->create([
            'nombre' => 'Campera Urbana',
            'categoria_id' => $category->id,
            'activo' => true,
            'precio_base' => '100.00',
            'costo_base' => '60.00',
        ]);
        $variant = Variante::query()->create([
            'producto_id' => $product->id,
            'sku' => 'CAMP-URB-001',
            'precio' => '100.00',
            'costo' => '60.00',
            'activo' => true,
        ]);
        $client = Cliente::query()->create([
            'dni' => '30123456',
            'nombre' => 'Ana',
            'apellido' => 'Suarez',
            'activo' => true,
        ]);

        $this->setCompanyData(
            nombre: 'VGC Original',
            razonSocial: 'VGC Original SRL',
            cuit: '20-11111111-1',
            direccion: 'San Martin 100',
            condicionFiscal: 'RESPONSABLE_INSCRIPTO',
        );

        $sale = Venta::query()->create([
            'sucursal_id' => $branch->id,
            'cajero_id' => $cashier->id,
            'cliente_id' => $client->id,
            'fecha' => now(),
            'estado' => Venta::ESTADO_CONFIRMADA,
            'medio_pago' => Venta::MEDIO_PAGO_CREDITO,
            'total' => '110.00',
            'numero_sucursal' => 1,
            'empresa_nombre_snapshot' => 'VGC Original',
            'empresa_razon_social_snapshot' => 'VGC Original SRL',
            'empresa_cuit_snapshot' => '20-11111111-1',
            'empresa_direccion_snapshot' => 'San Martin 100',
            'empresa_condicion_fiscal_snapshot' => 'RESPONSABLE_INSCRIPTO',
            'fiscal_items_sin_impuestos_nacionales' => '82.64',
            'fiscal_items_iva_contenido' => '17.36',
            'fiscal_items_otros_impuestos_nacionales_indirectos' => '0.00',
        ]);

        VentaItem::query()->create([
            'venta_id' => $sale->id,
            'variante_id' => $variant->id,
            'cantidad' => 1,
            'precio_unitario' => '100.00',
        ]);
        VentaPago::query()->create([
            'venta_id' => $sale->id,
            'tipo' => VentaPago::TIPO_CREDITO,
            'monto' => '100.00',
            'cuotas' => 3,
            'recargo_pct' => '10.00',
            'recargo_monto' => '10.00',
            'referencia' => 'Cupon 123',
        ]);

        $this->setCompanyData(
            nombre: 'VGC Nueva',
            razonSocial: 'VGC Nueva SA',
            cuit: '20-99999999-9',
            direccion: 'Belgrano 999',
            condicionFiscal: 'MONOTRIBUTISTA',
        );

        $this->actingAs($cashier)
            ->get(route('caja.ticket', $sale).'?print=1')
            ->assertOk()
            ->assertSee('VGC Original')
            ->assertSee('VGC Original SRL')
            ->assertSee('20-11111111-1')
            ->assertSee('San Martin 100')
            ->assertSee('Responsable Inscripto')
            ->assertSee('Ley 27.743')
            ->assertSee('Campera Urbana')
            ->assertSee('Base: $100,00')
            ->assertSee('Recargo: $10,00')
            ->assertSee('$110,00')
            ->assertSee('window.print();')
            ->assertDontSee('VGC Nueva')
            ->assertDontSee('VGC Nueva SA')
            ->assertDontSee('20-99999999-9')
            ->assertDontSee('Belgrano 999')
            ->assertDontSee('Monotributista');
    }

    protected function setCompanyData(
        string $nombre,
        string $razonSocial,
        string $cuit,
        string $direccion,
        string $condicionFiscal,
    ): void {
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.nombre'],
            ['value_str' => $nombre, 'description' => 'Nombre comercial'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.razon_social'],
            ['value_str' => $razonSocial, 'description' => 'Razon social'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.cuit'],
            ['value_str' => $cuit, 'description' => 'CUIT'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.direccion'],
            ['value_str' => $direccion, 'description' => 'Direccion'],
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'empresa.condicion_fiscal'],
            ['value_str' => $condicionFiscal, 'description' => 'Condicion fiscal'],
        );
    }
}
