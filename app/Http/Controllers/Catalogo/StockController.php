<?php

namespace App\Http\Controllers\Catalogo;

use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Models\StockSucursal;
use App\Domain\Catalogo\Support\CatalogoManager;
use App\Domain\Core\Models\Sucursal;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function update(Request $request, Producto $producto, CatalogoManager $manager): Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'sucursal_id' => ['required', Rule::exists('sucursales', 'id')],
            'stocks' => ['array'],
            'stocks.*' => ['nullable', 'integer', 'min:0'],
            'variante' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            if ($this->isHtmx($request)) {
                return $this->renderModalResponse(
                    $producto,
                    $manager,
                    $request->integer('sucursal_id') ?: null,
                    $request->integer('variante') ?: null,
                    null,
                    $validator->errors(),
                );
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $sucursal = Sucursal::query()
            ->where('activa', true)
            ->find($validated['sucursal_id']);

        if (! $sucursal) {
            $errors = new MessageBag([
                'sucursal_id' => 'La sucursal seleccionada no esta disponible para editar stock.',
            ]);

            if ($this->isHtmx($request)) {
                return $this->renderModalResponse(
                    $producto,
                    $manager,
                    (int) $validated['sucursal_id'],
                    (int) ($validated['variante'] ?? 0) ?: null,
                    null,
                    $errors,
                );
            }

            return back()
                ->withErrors($errors)
                ->withInput();
        }

        $stocks = collect($validated['stocks'] ?? [])
            ->mapWithKeys(fn ($cantidad, $varianteId) => [(int) $varianteId => (int) ($cantidad ?? 0)])
            ->all();

        $variantIds = $producto->variantes()->pluck('id');
        $invalidVariantIds = collect(array_keys($stocks))
            ->diff($variantIds)
            ->values();

        if ($invalidVariantIds->isNotEmpty()) {
            $errors = new MessageBag([
                'stocks' => 'Se recibieron variantes que no pertenecen al producto.',
            ]);

            if ($this->isHtmx($request)) {
                return $this->renderModalResponse(
                    $producto,
                    $manager,
                    $sucursal->id,
                    (int) ($validated['variante'] ?? 0) ?: null,
                    null,
                    $errors,
                );
            }

            return back()
                ->withErrors($errors)
                ->withInput();
        }

        DB::transaction(function () use ($stocks, $sucursal): void {
            foreach ($stocks as $varianteId => $cantidad) {
                StockSucursal::query()->updateOrCreate(
                    [
                        'sucursal_id' => $sucursal->id,
                        'variante_id' => $varianteId,
                    ],
                    ['cantidad' => $cantidad],
                );
            }
        });

        if ($this->isHtmx($request)) {
            return $this->renderModalResponse(
                $producto,
                $manager,
                $sucursal->id,
                (int) ($validated['variante'] ?? 0) ?: null,
                'Stock actualizado correctamente.',
            );
        }

        return redirect()
            ->route('catalogo.productos.show', [
                'producto' => $producto,
                'sucursal' => $sucursal->id,
            ])
            ->with('success', 'Stock actualizado correctamente.');
    }

    protected function renderModalResponse(
        Producto $producto,
        CatalogoManager $manager,
        ?int $sucursalId,
        ?int $focusedVariantId,
        ?string $successMessage,
        ?MessageBag $validationErrors = null,
        int $status = 200,
    ): Response {
        $producto->load('categoria');
        $selectedSucursal = null;

        if ($sucursalId) {
            $selectedSucursal = Sucursal::query()
                ->where('activa', true)
                ->find($sucursalId);
        }

        return response()->view('catalogo.partials.stock-modal-response', [
            'producto' => $producto,
            'stockMatrix' => $manager->buildStockMatrix($producto, $selectedSucursal),
            'focusedVariantId' => $focusedVariantId,
            'successMessage' => $successMessage,
            'validationErrors' => $validationErrors,
            'selectedProduct' => $producto,
            'selectedVariants' => $manager->variantsForProduct($producto),
        ], $status);
    }

    protected function isHtmx(Request $request): bool
    {
        return $request->header('HX-Request') === 'true';
    }
}
