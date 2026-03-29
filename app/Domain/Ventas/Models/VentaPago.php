<?php

namespace App\Domain\Ventas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaPago extends Model
{
    public const string TIPO_CONTADO = 'CONTADO';
    public const string TIPO_DEBITO = 'DEBITO';
    public const string TIPO_CREDITO = 'CREDITO';
    public const string TIPO_TRANSFERENCIA = 'TRANSFERENCIA';
    public const string TIPO_QR = 'QR';
    public const string TIPO_CUENTA_CORRIENTE = 'CUENTA_CORRIENTE';

    protected $table = 'venta_pagos';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'venta_id',
        'tipo',
        'monto',
        'cuotas',
        'coeficiente',
        'recargo_pct',
        'recargo_monto',
        'plan_id',
        'referencia',
        'pos_proveedor',
        'pos_terminal_id',
        'pos_lote',
        'pos_cupon',
        'pos_autorizacion',
        'pos_marca',
        'pos_ultimos4',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'cuotas' => 'integer',
            'coeficiente' => 'decimal:4',
            'recargo_pct' => 'decimal:2',
            'recargo_monto' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanCuotas::class, 'plan_id');
    }
}
