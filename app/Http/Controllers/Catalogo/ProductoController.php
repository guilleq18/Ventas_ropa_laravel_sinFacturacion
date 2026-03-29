<?php

namespace App\Http\Controllers\Catalogo;

use App\Domain\Catalogo\Models\Categoria;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Support\CatalogoManager;
use App\Domain\Core\Models\Sucursal;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function create(Request $request): View
    {
        if ($this->isHtmx($request)) {
            return view('catalogo.partials.product-form-modal-body', [
                'producto' => null,
                'categorias' => $this->categoriasDisponibles(),
                'values' => $this->productValues(),
                'searchQuery' => trim((string) $request->query('q', '')),
                'selectedProductId' => $request->integer('selected_product_id'),
                'successMessage' => null,
                'validationErrors' => null,
            ]);
        }

        return view('catalogo.producto-form', [
            'producto' => null,
            'categorias' => $this->categoriasDisponibles(),
        ]);
    }

    public function store(Request $request, CatalogoManager $manager): Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['nullable', Rule::exists('categorias', 'id')],
            'activo' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string'],
            'selected_product_id' => ['nullable', 'integer'],
        ]);

        $searchQuery = trim((string) $request->input('q', ''));
        $selectedProductId = $request->integer('selected_product_id');

        if ($validator->fails()) {
            if ($this->isHtmx($request)) {
                return $this->renderModalResponse(
                    null,
                    $manager,
                    $searchQuery,
                    $selectedProductId,
                    null,
                    $validator->errors(),
                    $this->productValues(null, $request),
                );
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $producto = Producto::query()->create([
            'nombre' => trim((string) $validated['nombre']),
            'descripcion' => trim((string) ($validated['descripcion'] ?? '')),
            'categoria_id' => $validated['categoria_id'] ?? null,
            'activo' => $request->boolean('activo', true),
            'precio_base' => '0.00',
            'costo_base' => '0.00',
        ]);

        if ($this->isHtmx($request)) {
            return $this->renderModalResponse(
                $producto->fresh(['categoria']) ?? $producto->load('categoria'),
                $manager,
                $searchQuery,
                $producto->id,
                "Producto creado: {$producto->nombre}.",
                null,
                $this->productValues($producto),
            );
        }

        return redirect()
            ->route('catalogo.productos.show', $producto)
            ->with('success', "Producto creado: {$producto->nombre}.");
    }

    public function show(Request $request, Producto $producto, CatalogoManager $manager): View
    {
        $producto->load('categoria');
        $selectedSucursal = null;
        $selectedSucursalId = $request->integer('sucursal');

        if ($selectedSucursalId > 0) {
            $selectedSucursal = Sucursal::query()
                ->where('activa', true)
                ->find($selectedSucursalId);
        }

        return view('catalogo.producto-show', [
            'producto' => $producto,
            'variantes' => $manager->variantsForProduct($producto),
            'stockMatrix' => $manager->buildStockMatrix($producto, $selectedSucursal),
        ]);
    }

    public function edit(Request $request, Producto $producto): View
    {
        if (! $this->isHtmx($request)) {
            return view('catalogo.producto-form', [
                'producto' => $producto,
                'categorias' => $this->categoriasDisponibles(),
            ]);
        }

        return view('catalogo.partials.product-form-modal-body', [
            'producto' => $producto,
            'categorias' => $this->categoriasDisponibles(),
            'values' => $this->productValues($producto),
            'searchQuery' => trim((string) $request->query('q', '')),
            'selectedProductId' => $request->integer('selected_product_id'),
            'successMessage' => null,
            'validationErrors' => null,
        ]);
    }

    public function update(Request $request, Producto $producto, CatalogoManager $manager): Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['nullable', Rule::exists('categorias', 'id')],
            'activo' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string'],
            'selected_product_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            if ($this->isHtmx($request)) {
                return $this->renderModalResponse(
                    $producto,
                    $manager,
                    trim((string) $request->input('q', '')),
                    $request->integer('selected_product_id'),
                    null,
                    $validator->errors(),
                    $this->productValues($producto, $request),
                );
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $producto->update([
            'nombre' => trim((string) $validated['nombre']),
            'descripcion' => trim((string) ($validated['descripcion'] ?? '')),
            'categoria_id' => $validated['categoria_id'] ?? null,
            'activo' => $request->boolean('activo', true),
        ]);

        if ($this->isHtmx($request)) {
            return $this->renderModalResponse(
                $producto->fresh(['categoria']) ?? $producto->load('categoria'),
                $manager,
                trim((string) ($validated['q'] ?? '')),
                (int) ($validated['selected_product_id'] ?? 0),
                "Producto actualizado: {$producto->nombre}.",
                null,
                $this->productValues($producto),
            );
        }

        return redirect()
            ->route('catalogo.productos.show', $producto)
            ->with('success', "Producto actualizado: {$producto->nombre}.");
    }

    public function toggle(Producto $producto): RedirectResponse
    {
        $producto->update([
            'activo' => ! $producto->activo,
        ]);

        $estado = $producto->activo ? 'activado' : 'desactivado';

        return redirect()
            ->route('catalogo.index', ['tab' => 'productos'])
            ->with('success', "Producto {$producto->nombre} {$estado}.");
    }

    protected function renderModalResponse(
        ?Producto $producto,
        CatalogoManager $manager,
        string $searchQuery,
        ?int $selectedProductId,
        ?string $successMessage,
        ?MessageBag $validationErrors,
        array $values,
        int $status = 200,
    ): Response {
        $productos = $this->filteredProducts($searchQuery);
        $selectedProduct = null;
        $selectedVariants = collect();

        if ($selectedProductId) {
            $selectedProduct = Producto::query()
                ->with('categoria')
                ->find($selectedProductId);

            if ($selectedProduct) {
                $selectedVariants = $manager->variantsForProduct($selectedProduct);
            }
        }

        return response()->view('catalogo.partials.product-form-modal-response', [
            'producto' => $producto,
            'categorias' => $this->categoriasDisponibles(),
            'values' => $values,
            'searchQuery' => $searchQuery,
            'selectedProductId' => $selectedProductId,
            'successMessage' => $successMessage,
            'validationErrors' => $validationErrors,
            'productos' => $productos,
            'selectedProduct' => $selectedProduct,
            'selectedVariants' => $selectedVariants,
        ], $status);
    }

    protected function productValues(?Producto $producto = null, ?Request $request = null): array
    {
        return [
            'nombre' => trim((string) ($request?->input('nombre') ?? $producto?->nombre ?? '')),
            'descripcion' => trim((string) ($request?->input('descripcion') ?? $producto?->descripcion ?? '')),
            'categoria_id' => $request?->input('categoria_id', $producto?->categoria_id),
            'activo' => $request ? $request->boolean('activo', $producto?->activo ?? true) : ($producto?->activo ?? true),
        ];
    }

    protected function filteredProducts(string $search): Collection
    {
        return Producto::query()
            ->with('categoria')
            ->withCount('variantes')
            ->when($search !== '', fn ($query) => $query->where('nombre', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    protected function categoriasDisponibles()
    {
        return Categoria::query()
            ->orderByDesc('activa')
            ->orderBy('nombre')
            ->get();
    }

    protected function isHtmx(Request $request): bool
    {
        return $request->header('HX-Request') === 'true';
    }
}
