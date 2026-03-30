<?php

namespace App\Domain\Fiscal\Models;

use App\Domain\Core\Models\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SucursalFiscalConfig extends Model
{
    public const string MODO_SOLO_REGISTRO = 'SOLO_REGISTRO';
    public const string MODO_FACTURAR_SI_SE_SOLICITA = 'FACTURAR_SI_SE_SOLICITA';
    public const string MODO_FACTURACION_OBLIGATORIA = 'FACTURACION_OBLIGATORIA';

    public const string ENTORNO_HOMOLOGACION = 'HOMOLOGACION';
    public const string ENTORNO_PRODUCCION = 'PRODUCCION';

    protected $table = 'sucursal_fiscal_configs';

    protected $fillable = [
        'sucursal_id',
        'modo_operacion',
        'entorno',
        'punto_venta',
        'facturacion_habilitada',
        'requiere_receptor_en_todas',
        'domicilio_fiscal_emision',
        'ultimo_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'punto_venta' => 'integer',
            'facturacion_habilitada' => 'boolean',
            'requiere_receptor_en_todas' => 'boolean',
            'ultimo_synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
