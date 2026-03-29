<?php

namespace App\Domain\Ventas\Models;

use App\Domain\Caja\Models\CajaSesion;
use App\Domain\Core\Models\Sucursal;
use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    public const int CODIGO_DIGITS = 11;

    public const string ESTADO_BORRADOR = 'BORRADOR';
    public const string ESTADO_CONFIRMADA = 'CONFIRMADA';
    public const string ESTADO_ANULADA = 'ANULADA';

    public const string MEDIO_PAGO_EFECTIVO = 'EFECTIVO';
    public const string MEDIO_PAGO_DEBITO = 'DEBITO';
    public const string MEDIO_PAGO_CREDITO = 'CREDITO';
    public const string MEDIO_PAGO_TRANSFERENCIA = 'TRANSFERENCIA';
    public const string MEDIO_PAGO_CUENTA_CORRIENTE = 'CUENTA_CORRIENTE';
    public const string MEDIO_PAGO_MIXTO = 'MIXTO';

    public $timestamps = false;

    protected $table = 'ventas';

    protected $fillable = [
        'sucursal_id',
        'numero_sucursal',
        'caja_sesion_id',
        'cajero_id',
        'fecha',
        'cliente_id',
        'estado',
        'medio_pago',
        'total',
        'empresa_nombre_snapshot',
        'empresa_razon_social_snapshot',
        'empresa_cuit_snapshot',
        'empresa_direccion_snapshot',
        'empresa_condicion_fiscal_snapshot',
        'fiscal_items_sin_impuestos_nacionales',
        'fiscal_items_iva_contenido',
        'fiscal_items_otros_impuestos_nacionales_indirectos',
    ];

    protected function casts(): array
    {
        return [
            'numero_sucursal' => 'integer',
            'fecha' => 'datetime',
            'total' => 'decimal:2',
            'fiscal_items_sin_impuestos_nacionales' => 'decimal:2',
            'fiscal_items_iva_contenido' => 'decimal:2',
            'fiscal_items_otros_impuestos_nacionales_indirectos' => 'decimal:2',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function cajaSesion(): BelongsTo
    {
        return $this->belongsTo(CajaSesion::class, 'caja_sesion_id');
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajero_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VentaItem::class, 'venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'venta_id');
    }

    public function movimientosCuentaCorriente(): HasMany
    {
        return $this->hasMany(MovimientoCuentaCorriente::class, 'venta_id');
    }

    public function getCodigoSucursalAttribute(): string
    {
        if ($this->numero_sucursal) {
            return 'V'.str_pad((string) $this->numero_sucursal, self::CODIGO_DIGITS, '0', STR_PAD_LEFT);
        }

        if ($this->id) {
            return '#'.$this->id;
        }

        return 's/n';
    }
}
