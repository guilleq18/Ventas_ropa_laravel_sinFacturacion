<?php

namespace App\Domain\Catalogo\Models;

use App\Domain\Core\Models\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSucursal extends Model
{
    protected $table = 'stock_sucursal';

    public const CREATED_AT = null;
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'sucursal_id',
        'variante_id',
        'cantidad',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function variante(): BelongsTo
    {
        return $this->belongsTo(Variante::class, 'variante_id');
    }
}
