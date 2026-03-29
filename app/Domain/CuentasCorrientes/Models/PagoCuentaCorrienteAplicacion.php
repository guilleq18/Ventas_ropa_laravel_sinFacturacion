<?php

namespace App\Domain\CuentasCorrientes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCuentaCorrienteAplicacion extends Model
{
    protected $table = 'pago_cuenta_corriente_aplicaciones';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'pago_cuenta_corriente_id',
        'movimiento_debito_id',
        'monto_aplicado',
    ];

    protected function casts(): array
    {
        return [
            'monto_aplicado' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(PagoCuentaCorriente::class, 'pago_cuenta_corriente_id');
    }

    public function movimientoDebito(): BelongsTo
    {
        return $this->belongsTo(MovimientoCuentaCorriente::class, 'movimiento_debito_id');
    }
}
