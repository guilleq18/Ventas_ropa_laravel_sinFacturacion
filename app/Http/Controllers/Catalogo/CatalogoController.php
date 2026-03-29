<?php

namespace App\Http\Controllers\Catalogo;

use App\Domain\Catalogo\Models\Categoria;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Support\CatalogoManager;
use App\Domain\Core\Models\Sucursal;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function __construct(
        protected CatalogoManager $catalogoManager,
    ) {
    }

    public function index(Request $request): View
    {
        $tab = $this->resolveTab($request);
        $categorias = Categoria::query()
            ->withCount('productos')
            ->orderByDesc('activa')
            ->orderBy('nombre')
            ->get();

        $productos = $this->filteredProductsQuery($request)
            ->withCount('variantes')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $editingCategory = null;
        $editingCategoryId = $request->integer('edit_categoria');

        if ($editingCategoryId > 0) {
            $editingCategory = $categorias->firstWhere('id', $editingCategoryId)
                ?? Categoria::query()->find($editingCategoryId);
            $tab = 'categorias';
        }

        $selectedProduct = null;
        $selectedVariants = collect();
        $selectedProductId = $request->integer('producto_id');

        if ($tab === 'productos' && $selectedProductId > 0) {
            $selectedProduct = Producto::query()
                ->with('categoria')
                ->find($selectedProductId);

            if ($selectedProduct) {
                $selectedVariants = $this->catalogoManager->variantsForProduct($selectedProduct);
            }
        }

        return view('catalogo.index', [
            'tab' => $tab,
            'categorias' => $categorias,
            'productos' => $productos,
            'selectedProduct' => $selectedProduct,
            'selectedVariants' => $selectedVariants,
            'editingCategory' => $editingCategory,
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
            ],
        ]);
    }

    public function productsTable(Request $request): View
    {
        $productos = $this->filteredProductsQuery($request)
            ->withCount('variantes')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('catalogo.partials.products-table', [
            'productos' => $productos,
            'selectedProductId' => $request->integer('selected_product_id'),
        ]);
    }

    public function productPanel(Producto $producto): View
    {
        $producto->load('categoria');

        return view('catalogo.partials.variants-panel', [
            'selectedProduct' => $producto,
            'selectedVariants' => $this->catalogoManager->variantsForProduct($producto),
        ]);
    }

    public function stockModal(Request $request, Producto $producto): View|RedirectResponse
    {
        if ($request->header('HX-Request') !== 'true') {
            return redirect()->route('catalogo.productos.show', [
                'producto' => $producto,
                'sucursal' => $request->integer('sucursal') ?: null,
            ]);
        }

        $producto->load('categoria');
        $selectedSucursal = null;
        $selectedSucursalId = $request->integer('sucursal');

        if ($selectedSucursalId > 0) {
            $selectedSucursal = Sucursal::query()
                ->where('activa', true)
                ->find($selectedSucursalId);
        }

        return view('catalogo.partials.stock-modal-body', [
            'producto' => $producto,
            'stockMatrix' => $this->catalogoManager->buildStockMatrix($producto, $selectedSucursal),
            'focusedVariantId' => $request->integer('variante') ?: null,
            'successMessage' => null,
            'validationErrors' => null,
        ]);
    }

    protected function filteredProductsQuery(Request $request): Builder
    {
        $query = Producto::query()->with('categoria');
        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where('nombre', 'like', "%{$search}%");
        }

        return $query;
    }

    protected function resolveTab(Request $request): string
    {
        return in_array($request->query('tab'), ['productos', 'categorias'], true)
            ? (string) $request->query('tab')
            : 'productos';
    }
}
