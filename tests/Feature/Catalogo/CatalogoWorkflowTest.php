<?php

namespace Tests\Feature\Catalogo;

use App\Domain\Catalogo\Models\Categoria;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Models\StockSucursal;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Catalogo\Support\CatalogoManager;
use App\Domain\Core\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_category_and_product(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('catalogo.categorias.store'), [
                'nombre' => 'Remeras',
                'activa' => '1',
            ])
            ->assertRedirect(route('catalogo.index', ['tab' => 'categorias']));

        $categoria = Categoria::query()->first();

        $this->assertNotNull($categoria);
        $this->assertSame('Remeras', $categoria->nombre);

        $this->actingAs($user)
            ->post(route('catalogo.productos.store'), [
                'nombre' => 'Remera Basica',
                'descripcion' => 'Algodon peinado',
                'categoria_id' => $categoria->id,
                'activo' => '1',
                'precio_base' => '14999.90',
                'costo_base' => '8500.50',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Remera Basica',
            'categoria_id' => $categoria->id,
        ]);
    }

    public function test_products_table_filters_by_search_term(): void
    {
        $user = User::factory()->create();

        Producto::query()->create([
            'nombre' => 'Jean Slim',
            'activo' => true,
            'precio_base' => '0.00',
            'costo_base' => '0.00',
        ]);
        Producto::query()->create([
            'nombre' => 'Remera Liso',
            'activo' => true,
            'precio_base' => '0.00',
            'costo_base' => '0.00',
        ]);

        $this->actingAs($user)
            ->get(route('catalogo.productos.table', ['q' => 'Jean']))
            ->assertOk()
            ->assertSee('Jean Slim')
            ->assertDontSee('Remera Liso');
    }

    public function test_user_can_create_variant_and_sync_attributes(): void
    {
        $user = User::factory()->create();
        $producto = Producto::query()->create([
            'nombre' => 'Buzo Canguro',
            'activo' => true,
            'precio_base' => '19999.00',
            'costo_base' => '12000.00',
        ]);

        $this->actingAs($user)
            ->post(route('catalogo.variantes.store', $producto), [
                'sku' => 'BUZO-NEG-M',
                'codigo_barras' => '7791234567890',
                'precio' => '22999.00',
                'costo' => '13000.00',
                'activo' => '1',
                'talle' => 'M',
                'color' => 'Negro',
            ])
            ->assertRedirect(route('catalogo.productos.show', $producto));

        $variante = Variante::query()->first();

        $this->assertNotNull($variante);
        $this->assertDatabaseHas('variante_atributos', [
            'variante_id' => $variante->id,
        ]);
        $this->assertDatabaseHas('atributos', ['nombre' => 'Talle']);
        $this->assertDatabaseHas('atributos', ['nombre' => 'Color']);
        $this->assertDatabaseHas('atributo_valores', ['valor' => 'M']);
        $this->assertDatabaseHas('atributo_valores', ['valor' => 'Negro']);
    }

    public function test_generator_skips_duplicates_and_stock_matrix_can_be_updated(): void
    {
        $user = User::factory()->create();
        $sucursal = Sucursal::query()->create([
            'nombre' => 'Casa Central',
            'activa' => true,
        ]);
        $producto = Producto::query()->create([
            'nombre' => 'Campera Puffer',
            'activo' => true,
            'precio_base' => '32000.00',
            'costo_base' => '21000.00',
        ]);

        $manager = app(CatalogoManager::class);
        $existingVariant = Variante::query()->create([
            'producto_id' => $producto->id,
            'sku' => 'CAMP-NEG-S',
            'precio' => '32000.00',
            'costo' => '21000.00',
            'activo' => true,
        ]);
        $manager->syncVariantAttributes($existingVariant, 'S', 'Negro');

        $this->actingAs($user)
            ->post(route('catalogo.variantes.generate', $producto), [
                'talles' => 'S,M',
                'colores' => 'Negro,Blanco',
                'codigo_barras_base' => '7790001112223',
                'precio' => '33000.00',
                'costo' => '22000.00',
                'activo' => '1',
            ])
            ->assertRedirect(route('catalogo.productos.show', $producto));

        $this->assertSame(4, Variante::query()->where('producto_id', $producto->id)->count());

        $variantId = Variante::query()
            ->where('producto_id', $producto->id)
            ->where('sku', 'CAMP-BLA-S')
            ->value('id');

        $this->assertNotNull($variantId);

        $this->actingAs($user)
            ->put(route('catalogo.stock.update', $producto), [
                'sucursal_id' => $sucursal->id,
                'stocks' => [
                    $variantId => 7,
                ],
            ])
            ->assertRedirect(route('catalogo.productos.show', [
                'producto' => $producto,
                'sucursal' => $sucursal->id,
            ]));

        $this->assertDatabaseHas('stock_sucursal', [
            'sucursal_id' => $sucursal->id,
            'variante_id' => $variantId,
            'cantidad' => 7,
        ]);
        $this->assertSame(7, StockSucursal::query()->first()->cantidad);
    }

    public function test_catalog_views_render_mobile_friendly_table_markers(): void
    {
        $user = User::factory()->create();
        $categoria = Categoria::query()->create([
            'nombre' => 'Pantalones',
            'activa' => true,
        ]);
        $producto = Producto::query()->create([
            'nombre' => 'Jean Mom',
            'categoria_id' => $categoria->id,
            'activo' => true,
            'precio_base' => '21999.00',
            'costo_base' => '14000.00',
        ]);
        $variante = Variante::query()->create([
            'producto_id' => $producto->id,
            'sku' => 'JEAN-AZU-38',
            'precio' => '23999.00',
            'costo' => '15000.00',
            'activo' => true,
        ]);

        app(CatalogoManager::class)->syncVariantAttributes($variante, '38', 'Azul');

        $this->actingAs($user)
            ->get(route('catalogo.index', ['tab' => 'categorias']))
            ->assertOk()
            ->assertSee('catalog-stack-table', false)
            ->assertSee('data-label="Productos"', false);

        $this->actingAs($user)
            ->get(route('catalogo.productos.panel', $producto))
            ->assertOk()
            ->assertSee('catalog-stack-table', false)
            ->assertSee('data-label="Stock total"', false);
    }

    public function test_variant_edit_modal_prefills_prices_from_requested_row_values(): void
    {
        $user = User::factory()->create();
        $producto = Producto::query()->create([
            'nombre' => 'Camisa Oxford',
            'activo' => true,
            'precio_base' => '18000.00',
            'costo_base' => '11000.00',
        ]);
        $variante = Variante::query()->create([
            'producto_id' => $producto->id,
            'sku' => 'CAM-OXF-M',
            'precio' => '21000.00',
            'costo' => '12000.00',
            'activo' => true,
        ]);

        app(CatalogoManager::class)->syncVariantAttributes($variante, 'M', 'Celeste');

        $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get(route('catalogo.variantes.edit', [
                'variante' => $variante,
                'selected_product_id' => $producto->id,
                'precio' => '24500.00',
                'costo' => '13250.00',
            ]))
            ->assertOk()
            ->assertSee('name="precio" value="24500.00"', false)
            ->assertSee('name="costo" value="13250.00"', false);
    }
}
