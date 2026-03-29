<?php

namespace App\Domain\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria_id',
        'activo',
        'precio_base',
        'costo_base',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'precio_base' => 'decimal:2',
            'costo_base' => 'decimal:2',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(Variante::class, 'producto_id');
    }
}
