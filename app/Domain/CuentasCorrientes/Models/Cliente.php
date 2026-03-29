<?php

namespace App\Domain\CuentasCorrientes\Models;

use App\Domain\Ventas\Models\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cliente extends Model
{
    protected $table = 'clientes';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = null;

    protected $fillable = [
        'dni',
        'nombre',
        'apellido',
        'telefono',
        'direccion',
        'fecha_nacimiento',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fecha_nacimiento' => 'date',
            'creado_en' => 'datetime',
        ];
    }

    public function cuentaCorriente(): HasOne
    {
        return $this->hasOne(CuentaCorriente::class, 'cliente_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->apellido}, {$this->nombre}", ', ');
    }
}
