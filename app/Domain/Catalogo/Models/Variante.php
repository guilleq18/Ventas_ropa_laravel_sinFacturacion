<?php

namespace App\Domain\Catalogo\Models;

use App\Domain\Ventas\Models\VentaItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variante extends Model
{
    protected $table = 'variantes';

    protected $fillable = [
        'producto_id',
        'sku',
        'codigo_barras',
        'precio',
        'costo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'costo' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function atributos(): HasMany
    {
        return $this->hasMany(VarianteAtributo::class, 'variante_id');
    }

    public function stockSucursales(): HasMany
    {
        return $this->hasMany(StockSucursal::class, 'variante_id');
    }

    public function ventaItems(): HasMany
    {
        return $this->hasMany(VentaItem::class, 'variante_id');
    }
}
