<?php

namespace App\Http\Controllers\Catalogo;

use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Catalogo\Support\CatalogoManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogo\VariantRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VarianteController extends Controller
{
    public function create(Producto $producto): View
    {
        return view('catalogo.variante-form', [
            'producto' => $producto,
            'variante' => null,
            'values' => [
                'sku' => '',
                'codigo_barras' => '',
                'precio' => $producto->precio_base,
                'costo' => $producto->costo_base,
                'activo' => true,
                'talle' => '',
                'color' => '',
            ],
        ]);
    }

    public function generator(Request $request, Producto $producto): View|RedirectResponse
    {
        if (! $this->isHtmx($request)) {
            return redirect()->route('catalogo.productos.show', $producto);
        }

        return view('catalogo.partials.generator-form-modal-body', [
            'producto' => $producto,
            'values' => $this->generatorValues($producto),
            'selectedProductId' => $request->integer('selected_product_id') ?: $producto->id,
            'successMessage' => null,
            'validationErrors' => null,
        ]);
    }

    public function store(
        VariantRequest $request,
        Producto $producto,
        CatalogoManager $manager,
    ): RedirectResponse {
        $payload = $request->validatedPayload();
        $this->ensureVariantCombinationIsUnique(
            $manager,
            $producto,
            $payload['talle'],
            $payload['color'],
        );

        try {
            DB::transaction(function () use ($producto, $payload, $manager): void {
                /** @var Variante $variante */
                $variante = $producto->variantes()->create([
                    'sku' => $payload['sku'],
                    'codigo_barras' => $payload['codigo_barras'],
                    'precio' => $payload['precio'],
                    'costo' => $payload['costo'],
                    'activo' => $payload['activo'],
                ]);

                $manager->syncVariantAttributes($variante, $payload['talle'], $payload['color']);
            });
        } catch (QueryException $exception) {
            $this->throwSkuValidationIfNeeded($exception);

            throw $exception;
        }

        return redirect()
            ->route('catalogo.productos.show', $producto)
            ->with('success', 'Variante creada correctamente.');
    }

    public function edit(Request $request, Variante $variante, CatalogoManager $manager): View
    {
        $variante->load('producto');
        $values = $this->variantValues($variante, $manager, $request);

        if (! $this->isHtmx($request)) {
            return view('catalogo.variante-form', [
                'producto' => $variante->producto,
                'variante' => $variante,
                'values' => $values,
            ]);
        }

        return view('catalogo.partials.variant-form-modal-body', [
            'producto' => $variante->producto,
            'variante' => $variante,
            'values' => $values,
            'selectedProductId' => $request->integer('selected_product_id') ?: $variante->producto_id,
            'successMessage' => null,
            'validationErrors' => null,
        ]);
    }

    public function update(
        Request $request,
        Variante $variante,
        CatalogoManager $manager,
    ): Response|RedirectResponse {
        $variante->load('producto');
        $validator = Validator::make($request->all(), [
            'sku' => ['required', 'string', 'max:64'],
            'codigo_barras' => ['nullable', 'string', 'max:64'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
            'talle' => ['required', 'string', 'max:60'],
            'color' => ['required', 'string', 'max:60'],
            'selected_product_id' => ['nullable', 'integer'],
        ]);

        $selectedProductId = $request->integer('selected_product_id') ?: $variante->producto_id;

        if ($validator->fails()) {
            if ($this->isHtmx($request)) {
                return $this->renderModalResponse(
                    $variante,
                    $manager,
                    $selectedProductId,
                    null,
                    $validator->errors(),
                    $this->variantValues($variante, $manager, $request),
                );
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $payload = [
            'sku' => trim((string) $validated['sku']),
            'codigo_barras' => trim((string) ($validated['codigo_barras'] ?? '')) ?: null,
            'precio' => $validated['precio'],
            'costo' => $validated['costo'],
            'activo' => $request->boolean('activo', true),
            'talle' => trim((string) $validated['talle']),
            'color' => trim((string) $validated['color']),
        ];

        try {
            $this->ensureVariantCombinationIsUnique(
                $manager,
                $variante->producto,
                $payload['talle'],
                $payload['color'],
                $variante,
            );

            DB::transaction(function () use ($variante, $payload, $manager): void {
                $variante->update([
                    'sku' => $payload['sku'],
                    'codigo_barras' => $payload['codigo_barras'],
                    'precio' => $payload['precio'],
                    'costo' => $payload['costo'],
                    'activo' => $payload['activo'],
                ]);

                $manager->syncVariantAttributes($variante, $payload['talle'], $payload['color']);
            });
        } catch (ValidationException $exception) {
            $errors = new MessageBag($exception->errors());

            if ($this->isHtmx($request)) {
                return $this->renderModalResponse(
                    $variante,
                    $manager,
                    $selectedProductId,
                    null,
                    $errors,
                    $payload,
                );
            }

            return back()
                ->withErrors($errors)
                ->withInput();
        } catch (QueryException $exception) {
            if (Str::contains($exception->getMessage(), ['UNIQUE constraint failed: variantes.sku', 'Duplicate entry'])) {
                $errors = new MessageBag([
                    'sku' => 'El SKU ya esta en uso. Elegi otro valor.',
                ]);

                if ($this->isHtmx($request)) {
                    return $this->renderModalResponse(
                        $variante,
                        $manager,
                        $selectedProductId,
                        null,
                        $errors,
                        $payload,
                    );
                }

                return back()
                    ->withErrors($errors)
                    ->withInput();
            }

            throw $exception;
        }

        $variante = $variante->fresh(['producto']) ?? $variante->load('producto');

        if ($this->isHtmx($request)) {
            return $this->renderModalResponse(
                $variante,
                $manager,
                $selectedProductId,
                'Variante actualizada correctamente.',
                null,
                $this->variantValues($variante, $manager),
            );
        }

        return redirect()
            ->route('catalogo.productos.show', $variante->producto)
            ->with('success', 'Variante actualizada correctamente.');
    }

    public function destroy(Variante $variante): RedirectResponse
    {
        $producto = $variante->producto;
        $variante->delete();

        return redirect()
            ->route('catalogo.productos.show', $producto)
            ->with('success', 'Variante eliminada correctamente.');
    }

    public function generate(
        Request $request,
        Producto $producto,
        CatalogoManager $manager,
    ): Response|RedirectResponse {
        $validator = Validator::make($request->all(), [
            'talles' => ['required', 'string'],
            'colores' => ['required', 'string'],
            'codigo_barras_base' => ['nullable', 'string', 'max:64'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
            'selected_product_id' => ['nullable', 'integer'],
        ]);

        $selectedProductId = $request->integer('selected_product_id') ?: $producto->id;

        if ($validator->fails()) {
            if ($this->isHtmx($request)) {
                return $this->renderGeneratorModalResponse(
                    $producto,
                    $manager,
                    $selectedProductId,
                    null,
                    $validator->errors(),
                    $this->generatorValues($producto, $request),
                );
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $talles = collect(explode(',', (string) $validated['talles']))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
        $colores = collect(explode(',', (string) $validated['colores']))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
        $payload = [
            'codigo_barras_base' => trim((string) ($validated['codigo_barras_base'] ?? '')),
            'precio' => (string) $validated['precio'],
            'costo' => (string) $validated['costo'],
            'activo' => $request->boolean('activo', true),
        ];
        $generated = $manager->generateVariants(
            $producto,
            $talles,
            $colores,
            $payload['codigo_barras_base'],
            $payload['precio'],
            $payload['costo'],
            $payload['activo'],
        );

        $message = $generated > 0
            ? "Se generaron {$generated} variantes nuevas."
            : 'No habia combinaciones nuevas para generar.';

        if ($this->isHtmx($request)) {
            return $this->renderGeneratorModalResponse(
                $producto,
                $manager,
                $selectedProductId,
                $message,
                null,
                $generated > 0
                    ? $this->generatorValues($producto)
                    : $this->generatorValues($producto, $request),
            );
        }

        return redirect()
            ->route('catalogo.productos.show', $producto)
            ->with('success', $message);
    }

    protected function renderModalResponse(
        Variante $variante,
        CatalogoManager $manager,
        int $selectedProductId,
        ?string $successMessage,
        ?MessageBag $validationErrors,
        array $values,
        int $status = 200,
    ): Response {
        $variante->loadMissing('producto');
        $selectedProduct = null;
        $selectedVariants = collect();

        if ($selectedProductId > 0) {
            $selectedProduct = Producto::query()
                ->with('categoria')
                ->find($selectedProductId);

            if ($selectedProduct) {
                $selectedVariants = $manager->variantsForProduct($selectedProduct);
            }
        }

        return response()->view('catalogo.partials.variant-form-modal-response', [
            'producto' => $variante->producto,
            'variante' => $variante,
            'values' => $values,
            'selectedProductId' => $selectedProductId,
            'successMessage' => $successMessage,
            'validationErrors' => $validationErrors,
            'selectedProduct' => $selectedProduct,
            'selectedVariants' => $selectedVariants,
        ], $status);
    }

    protected function renderGeneratorModalResponse(
        Producto $producto,
        CatalogoManager $manager,
        int $selectedProductId,
        ?string $successMessage,
        ?MessageBag $validationErrors,
        array $values,
        int $status = 200,
    ): Response {
        $producto->loadMissing('categoria');

        return response()->view('catalogo.partials.generator-form-modal-response', [
            'producto' => $producto,
            'values' => $values,
            'selectedProductId' => $selectedProductId > 0 ? $selectedProductId : $producto->id,
            'successMessage' => $successMessage,
            'validationErrors' => $validationErrors,
            'selectedProduct' => $producto,
            'selectedVariants' => $manager->variantsForProduct($producto),
        ], $status);
    }

    protected function variantValues(Variante $variante, CatalogoManager $manager, ?Request $request = null): array
    {
        [$talle, $color] = $manager->extractTalleColor($variante);

        return [
            'sku' => trim((string) ($request?->input('sku') ?? $variante->sku)),
            'codigo_barras' => trim((string) ($request?->input('codigo_barras') ?? $variante->codigo_barras)),
            'precio' => $request?->input('precio', $variante->precio),
            'costo' => $request?->input('costo', $variante->costo),
            'activo' => $request ? $request->boolean('activo', $variante->activo) : $variante->activo,
            'talle' => trim((string) ($request?->input('talle') ?? $talle)),
            'color' => trim((string) ($request?->input('color') ?? $color)),
        ];
    }

    protected function generatorValues(Producto $producto, ?Request $request = null): array
    {
        return [
            'talles' => trim((string) $request?->input('talles', '')),
            'colores' => trim((string) $request?->input('colores', '')),
            'codigo_barras_base' => trim((string) $request?->input('codigo_barras_base', '')),
            'precio' => $request?->input('precio', $producto->precio_base),
            'costo' => $request?->input('costo', $producto->costo_base),
            'activo' => $request ? $request->boolean('activo', true) : true,
        ];
    }

    protected function ensureVariantCombinationIsUnique(
        CatalogoManager $manager,
        Producto $producto,
        string $talle,
        string $color,
        ?Variante $except = null,
    ): void {
        if (! $manager->variantCombinationExists($producto, $talle, $color, $except)) {
            return;
        }

        throw ValidationException::withMessages([
            'talle' => "Ya existe una variante con Talle={$talle} y Color={$color}.",
        ]);
    }

    protected function throwSkuValidationIfNeeded(QueryException $exception): void
    {
        $message = $exception->getMessage();

        if (! Str::contains($message, ['UNIQUE constraint failed: variantes.sku', 'Duplicate entry'])) {
            return;
        }

        throw ValidationException::withMessages([
            'sku' => 'El SKU ya esta en uso. Elegi otro valor.',
        ]);
    }

    protected function isHtmx(Request $request): bool
    {
        return $request->header('HX-Request') === 'true';
    }
}
