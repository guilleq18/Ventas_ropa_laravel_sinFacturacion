<?php

namespace App\Domain\Caja\Models;

use App\Domain\Core\Models\Sucursal;
use App\Domain\Ventas\Models\Venta;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaSesion extends Model
{
    public $timestamps = false;

    protected $table = 'caja_sesiones';

    protected $fillable = [
        'sucursal_id',
        'cajero_apertura_id',
        'abierta_en',
        'cajero_cierre_id',
        'cerrada_en',
        'abierta_marker',
    ];

    protected function casts(): array
    {
        return [
            'abierta_en' => 'datetime',
            'cerrada_en' => 'datetime',
            'abierta_marker' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $sesion): void {
            $sesion->abierta_marker = $sesion->cerrada_en ? null : 1;
        });
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function cajeroApertura(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajero_apertura_id');
    }

    public function cajeroCierre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajero_cierre_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'caja_sesion_id');
    }

    public function getEstaAbiertaAttribute(): bool
    {
        return $this->cerrada_en === null;
    }

    public function cerrar(?User $user = null): void
    {
        if ($this->cerrada_en === null) {
            $this->cerrada_en = now();
        }

        if ($user !== null) {
            $this->cajero_cierre_id = $user->id;
        }
    }
}
