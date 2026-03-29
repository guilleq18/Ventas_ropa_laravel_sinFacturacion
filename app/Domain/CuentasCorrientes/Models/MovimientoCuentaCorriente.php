<?php

namespace App\Domain\CuentasCorrientes\Models;

use App\Domain\Ventas\Models\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class MovimientoCuentaCorriente extends Model
{
    public const string TIPO_DEBITO = 'DEBITO';
    public const string TIPO_CREDITO = 'CREDITO';

    protected $table = 'movimientos_cuenta_corriente';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'cuenta_id',
        'tipo',
        'monto',
        'fecha',
        'venta_id',
        'referencia',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $movimiento): void {
            if ($movimiento->tipo === self::TIPO_DEBITO && ! $movimiento->venta_id) {
                throw ValidationException::withMessages([
                    'venta_id' => 'Un movimiento DEBITO deberia estar asociado a una venta.',
                ]);
            }

            if ($movimiento->tipo === self::TIPO_CREDITO && $movimiento->venta_id) {
                throw ValidationException::withMessages([
                    'venta_id' => 'Un movimiento CREDITO no deberia estar asociado a una venta.',
                ]);
            }
        });
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaCorriente::class, 'cuenta_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function pagoCuentaCorriente(): HasOne
    {
        return $this->hasOne(PagoCuentaCorriente::class, 'movimiento_credito_id');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(PagoCuentaCorrienteAplicacion::class, 'movimiento_debito_id');
    }
}
