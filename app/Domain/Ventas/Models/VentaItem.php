<?php

namespace App\Domain\Ventas\Models;

use App\Domain\Catalogo\Models\Variante;
use App\Support\Fiscal\FiscalMath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaItem extends Model
{
    public $timestamps = false;

    protected $table = 'venta_items';

    protected $fillable = [
        'venta_id',
        'variante_id',
        'cantidad',
        'precio_unitario',
        'iva_alicuota_pct',
        'subtotal',
        'precio_unitario_sin_impuestos_nacionales',
        'precio_unitario_iva_contenido',
        'subtotal_sin_impuestos_nacionales',
        'subtotal_iva_contenido',
        'subtotal_otros_impuestos_nacionales_indirectos',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'iva_alicuota_pct' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'precio_unitario_sin_impuestos_nacionales' => 'decimal:2',
            'precio_unitario_iva_contenido' => 'decimal:2',
            'subtotal_sin_impuestos_nacionales' => 'decimal:2',
            'subtotal_iva_contenido' => 'decimal:2',
            'subtotal_otros_impuestos_nacionales_indirectos' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $item->subtotal = FiscalMath::multiplyMoney(
                $item->cantidad,
                $item->precio_unitario ?? '0',
            );

            $unitario = FiscalMath::desglosarMontoFinalGravadoConIva(
                $item->precio_unitario ?? '0',
                $item->iva_alicuota_pct ?? FiscalMath::IVA_GENERAL_PCT,
            );
            $subtotal = FiscalMath::desglosarMontoFinalGravadoConIva(
                $item->subtotal ?? '0',
                $item->iva_alicuota_pct ?? FiscalMath::IVA_GENERAL_PCT,
            );

            $item->precio_unitario_sin_impuestos_nacionales = $unitario['monto_sin_impuestos_nacionales'];
            $item->precio_unitario_iva_contenido = $unitario['iva_contenido'];
            $item->subtotal_sin_impuestos_nacionales = $subtotal['monto_sin_impuestos_nacionales'];
            $item->subtotal_iva_contenido = $subtotal['iva_contenido'];
            $item->subtotal_otros_impuestos_nacionales_indirectos = $subtotal['otros_impuestos_nacionales_indirectos'];
        });
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function variante(): BelongsTo
    {
        return $this->belongsTo(Variante::class, 'variante_id');
    }
}
