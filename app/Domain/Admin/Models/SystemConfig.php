<?php

namespace App\Domain\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $table = 'system_configs';

    public const CREATED_AT = null;
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'permitir_vender_sin_stock',
        'permitir_cambiar_precio_venta',
    ];

    protected function casts(): array
    {
        return [
            'permitir_vender_sin_stock' => 'boolean',
            'permitir_cambiar_precio_venta' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }
}
