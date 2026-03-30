<?php

namespace App\Domain\Core\Models;

use App\Domain\Admin\Models\UserProfile;
use App\Domain\Caja\Models\CajaSesion;
use App\Domain\Catalogo\Models\StockSucursal;
use App\Domain\Fiscal\Models\SucursalFiscalConfig;
use App\Domain\Fiscal\Models\VentaComprobante;
use App\Domain\Ventas\Models\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function fiscalConfig(): HasOne
    {
        return $this->hasOne(SucursalFiscalConfig::class, 'sucursal_id');
    }

    public function comprobantesFiscales(): HasMany
    {
        return $this->hasMany(VentaComprobante::class, 'sucursal_id');
    }

    public function stockSucursales(): HasMany
    {
        return $this->hasMany(StockSucursal::class, 'sucursal_id');
    }
}
