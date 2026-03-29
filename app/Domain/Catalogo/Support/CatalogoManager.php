<?php

namespace App\Domain\Catalogo\Support;

use App\Domain\Catalogo\Models\Atributo;
use App\Domain\Catalogo\Models\AtributoValor;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\Catalogo\Models\StockSucursal;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Catalogo\Models\VarianteAtributo;
use App\Domain\Core\Models\Sucursal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogoManager
{
    public function variantsForProduct(Producto $producto): Collection
    {
        return $producto->variantes()
            ->with(['atributos.atributo', 'atributos.valor'])
            ->withSum('stockSucursales as stock_total', 'cantidad')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Variante $variante): array {
                [$talle, $color] = $this->extractTalleColor($variante);

                return [
                    'variante' => $variante,
                    'talle' => $talle ?: '-',
                    'color' => $color ?: '-',
                    'stock_total' => (int) ($variante->stock_total ?? 0),
                ];
            });
    }

    public function extractTalleColor(Variante $variante): array
    {
        $variante->loadMissing('atributos.atributo', 'atributos.valor');

        $talle = '';
        $color = '';

        foreach ($variante->atributos as $atributo) {
            if ($atributo->atributo?->nombre === 'Talle') {
                $talle = $atributo->valor?->valor ?? '';
            }

            if ($atributo->atributo?->nombre === 'Color') {
                $color = $atributo->valor?->valor ?? '';
            }
        }

        return [$talle, $color];
    }

    public function variantCombinationExists(
        Producto $producto,
        string $talle,
        string $color,
        ?Variante $except = null,
    ): bool {
        $talle = $this->normalizeKey($talle);
        $color = $this->normalizeKey($color);

        $query = $producto->variantes()->with(['atributos.atributo', 'atributos.valor']);

        if ($except) {
            $query->whereKeyNot($except->getKey());
        }

        foreach ($query->get() as $variante) {
            [$existingTalle, $existingColor] = $this->extractTalleColor($variante);

            if (
                $this->normalizeKey($existingTalle) === $talle
                && $this->normalizeKey($existingColor) === $color
            ) {
                return true;
            }
        }

        return false;
    }

    public function syncVariantAttributes(Variante $variante, string $talle, string $color): void
    {
        $atributoTalle = Atributo::query()->firstOrCreate(
            ['nombre' => 'Talle'],
            ['activo' => true],
        );
        $atributoColor = Atributo::query()->firstOrCreate(
            ['nombre' => 'Color'],
            ['activo' => true],
        );

        $valorTalle = AtributoValor::query()->firstOrCreate(
            [
                'atributo_id' => $atributoTalle->id,
                'valor' => trim($talle),
            ],
            ['activo' => true],
        );
        $valorColor = AtributoValor::query()->firstOrCreate(
            [
                'atributo_id' => $atributoColor->id,
                'valor' => trim($color),
            ],
            ['activo' => true],
        );

        VarianteAtributo::query()->updateOrCreate(
            [
                'variante_id' => $variante->id,
                'atributo_id' => $atributoTalle->id,
            ],
            ['valor_id' => $valorTalle->id],
        );
        VarianteAtributo::query()->updateOrCreate(
            [
                'variante_id' => $variante->id,
                'atributo_id' => $atributoColor->id,
            ],
            ['valor_id' => $valorColor->id],
        );
    }

    public function generateSku(string $nombreProducto, string $color, string $talle): string
    {
        $producto = Str::substr($this->skuClean($nombreProducto), 0, 4) ?: 'PROD';
        $colorPart = Str::substr($this->skuClean($color), 0, 3) ?: 'SIN';
        $tallePart = $this->skuClean($talle) ?: 'U';

        return "{$producto}-{$colorPart}-{$tallePart}";
    }

    public function buildStockMatrix(Producto $producto, ?Sucursal $selectedSucursal = null): array
    {
        $sucursales = Sucursal::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->get();

        $selectedSucursal ??= $sucursales->first();

        $variantes = $producto->variantes()
            ->with(['atributos.atributo', 'atributos.valor'])
            ->orderBy('sku')
            ->get();

        $talles = [];
        $colores = [];
        $combos = [];

        foreach ($variantes as $variante) {
            [$talle, $color] = $this->extractTalleColor($variante);
            $talle = $talle ?: '-';
            $color = $color ?: '-';

            $talles[$talle] = true;
            $colores[$color] = true;
            $combos[$talle][$color] = $variante;
        }

        $stockMap = [];

        if ($selectedSucursal) {
            $stockMap = StockSucursal::query()
                ->where('sucursal_id', $selectedSucursal->id)
                ->whereIn('variante_id', $variantes->pluck('id'))
                ->pluck('cantidad', 'variante_id')
                ->map(fn ($cantidad) => (int) $cantidad)
                ->all();
        }

        $rows = [];
        $sortedTalles = collect(array_keys($talles))->sort()->values();
        $sortedColores = collect(array_keys($colores))->sort()->values();

        foreach ($sortedTalles as $talle) {
            $cells = [];

            foreach ($sortedColores as $color) {
                /** @var Variante|null $variante */
                $variante = $combos[$talle][$color] ?? null;

                $cells[] = [
                    'color' => $color,
                    'variante' => $variante,
                    'cantidad' => $variante ? ($stockMap[$variante->id] ?? 0) : null,
                ];
            }

            $rows[] = [
                'talle' => $talle,
                'cells' => $cells,
            ];
        }

        return [
            'sucursales' => $sucursales,
            'sucursalSeleccionada' => $selectedSucursal,
            'colores' => $sortedColores,
            'rows' => $rows,
        ];
    }

    public function generateVariants(
        Producto $producto,
        array $talles,
        array $colores,
        string $codigoBarrasBase,
        string $precio,
        string $costo,
        bool $activo,
    ): int {
        $talles = collect($talles)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
        $colores = collect($colores)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        $existingSku = Variante::query()->pluck('sku')->flip();
        $generated = 0;

        DB::transaction(function () use (
            $producto,
            $talles,
            $colores,
            $codigoBarrasBase,
            $precio,
            $costo,
            $activo,
            $existingSku,
            &$generated,
        ): void {
            foreach ($talles as $talle) {
                foreach ($colores as $color) {
                    if ($this->variantCombinationExists($producto, $talle, $color)) {
                        continue;
                    }

                    $baseSku = $this->generateSku($producto->nombre, $color, $talle);
                    $sku = $baseSku;
                    $counter = 2;

                    while ($existingSku->has($sku)) {
                        $sku = "{$baseSku}-{$counter}";
                        $counter++;
                    }

                    /** @var Variante $variante */
                    $variante = $producto->variantes()->create([
                        'sku' => $sku,
                        'codigo_barras' => trim($codigoBarrasBase) ?: null,
                        'precio' => $precio,
                        'costo' => $costo,
                        'activo' => $activo,
                    ]);

                    $existingSku->put($sku, true);
                    $this->syncVariantAttributes($variante, $talle, $color);
                    $generated++;
                }
            }
        });

        return $generated;
    }

    protected function normalizeKey(string $value): string
    {
        return trim($value);
    }

    protected function skuClean(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', Str::upper(Str::ascii(trim($value)))) ?: '';
    }
}
