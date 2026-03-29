<?php

namespace Tests\Feature\Domain;

use App\Domain\Caja\Models\CajaSesion;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Core\Models\Sucursal;
use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente;
use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Models\VentaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DomainModelIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_venta_item_calcula_subtotal_y_snapshot_fiscal(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);
        $producto = Producto::create([
            'nombre' => 'Remera Basica',
            'activo' => true,
            'precio_base' => '0.00',
            'costo_base' => '0.00',
        ]);
        $variante = Variante::create([
            'producto_id' => $producto->id,
            'sku' => 'REM-NEG-M',
            'precio' => '121.00',
            'costo' => '80.00',
            'activo' => true,
        ]);
        $venta = Venta::create([
            'sucursal_id' => $sucursal->id,
            'estado' => Venta::ESTADO_BORRADOR,
            'medio_pago' => Venta::MEDIO_PAGO_EFECTIVO,
            'total' => '0.00',
        ]);

        $item = VentaItem::create([
            'venta_id' => $venta->id,
            'variante_id' => $variante->id,
            'cantidad' => 2,
            'precio_unitario' => '121.00',
        ]);

        $this->assertSame('242.00', $item->subtotal);
        $this->assertSame('100.00', $item->precio_unitario_sin_impuestos_nacionales);
        $this->assertSame('21.00', $item->precio_unitario_iva_contenido);
        $this->assertSame('200.00', $item->subtotal_sin_impuestos_nacionales);
        $this->assertSame('42.00', $item->subtotal_iva_contenido);
    }

    public function test_caja_sesion_usa_abierta_marker_para_restringir_una_abierta_por_sucursal(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'Sucursal Norte',
            'activa' => true,
        ]);
        $user = User::factory()->create();

        $abierta = CajaSesion::create([
            'sucursal_id' => $sucursal->id,
            'cajero_apertura_id' => $user->id,
        ]);

        $this->assertSame(1, $abierta->abierta_marker);
        $this->assertTrue($abierta->esta_abierta);

        $abierta->cerrar($user);
        $abierta->save();
        $abierta->refresh();

        $this->assertNull($abierta->abierta_marker);
        $this->assertFalse($abierta->esta_abierta);
    }

    public function test_movimiento_debito_requiere_venta(): void
    {
        $cliente = Cliente::create([
            'dni' => '12345678',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'activo' => true,
        ]);
        $cuenta = CuentaCorriente::create([
            'cliente_id' => $cliente->id,
            'activa' => true,
        ]);

        $this->expectException(ValidationException::class);

        MovimientoCuentaCorriente::create([
            'cuenta_id' => $cuenta->id,
            'tipo' => MovimientoCuentaCorriente::TIPO_DEBITO,
            'monto' => '100.00',
        ]);
    }

    public function test_movimiento_credito_no_debe_tener_venta(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'Sucursal Sur',
            'activa' => true,
        ]);
        $cliente = Cliente::create([
            'dni' => '87654321',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'activo' => true,
        ]);
        $cuenta = CuentaCorriente::create([
            'cliente_id' => $cliente->id,
            'activa' => true,
        ]);
        $venta = Venta::create([
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente->id,
            'estado' => Venta::ESTADO_BORRADOR,
            'medio_pago' => Venta::MEDIO_PAGO_EFECTIVO,
            'total' => '0.00',
        ]);

        $this->expectException(ValidationException::class);

        MovimientoCuentaCorriente::create([
            'cuenta_id' => $cuenta->id,
            'tipo' => MovimientoCuentaCorriente::TIPO_CREDITO,
            'monto' => '100.00',
            'venta_id' => $venta->id,
        ]);
    }
}
