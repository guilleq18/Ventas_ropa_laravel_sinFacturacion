<?php

namespace App\Domain\CuentasCorrientes\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaCorriente extends Model
{
    protected $table = 'cuentas_corrientes';

    public const CREATED_AT = 'creada_en';
    public const UPDATED_AT = null;

    protected $fillable = [
        'cliente_id',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'creada_en' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCuentaCorriente::class, 'cuenta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoCuentaCorriente::class, 'cuenta_id');
    }

    public function saldo(): string
    {
        $debitos = (string) ($this->movimientos()
            ->where('tipo', MovimientoCuentaCorriente::TIPO_DEBITO)
            ->sum('monto') ?: '0');
        $creditos = (string) ($this->movimientos()
            ->where('tipo', MovimientoCuentaCorriente::TIPO_CREDITO)
            ->sum('monto') ?: '0');

        return BigDecimal::of($debitos)
            ->minus(BigDecimal::of($creditos))
            ->toScale(2, RoundingMode::HALF_UP)
            ->__toString();
    }
}
