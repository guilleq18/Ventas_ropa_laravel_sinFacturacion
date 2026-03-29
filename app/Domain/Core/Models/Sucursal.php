<?php

namespace App\Domain\Core\Models;

use App\Domain\Admin\Models\UserProfile;
use App\Domain\Caja\Models\CajaSesion;
use App\Domain\Catalogo\Models\StockSucursal;
use App\Domain\Ventas\Models\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    public $timestamps = false;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    public function userProfiles(): HasMany
    {
        return $this->hasMany(UserProfile::class, 'sucursal_id');
    }

    public function cajasSesiones(): HasMany
    {
        return $this->hasMany(CajaSesion::class, 'sucursal_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'sucursal_id');
    }

    public function stockSucursales(): HasMany
    {
        return $this->hasMany(StockSucursal::class, 'sucursal_id');
    }
}
