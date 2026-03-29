<?php

namespace App\Domain\CuentasCorrientes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoCuentaCorriente extends Model
{
    protected $table = 'pagos_cuenta_corriente';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'cuenta_id',
        'movimiento_credito_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaCorriente::class, 'cuenta_id');
    }

    public function movimientoCredito(): BelongsTo
    {
        return $this->belongsTo(MovimientoCuentaCorriente::class, 'movimiento_credito_id');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(PagoCuentaCorrienteAplicacion::class, 'pago_cuenta_corriente_id');
    }
}
