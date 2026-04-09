<?php

namespace App\Domain\Fiscal\Models;

use App\Domain\Core\Models\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArcaCaeaComprobante extends Model
{
    public const string ESTADO_RENDICION_PENDIENTE = 'PENDIENTE';
    public const string ESTADO_RENDICION_INFORMADO = 'INFORMADO';
    public const string ESTADO_RENDICION_OBSERVADO = 'OBSERVADO';
    public const string ESTADO_RENDICION_RECHAZADO = 'RECHAZADO';

    protected $table = 'arca_caea_comprobantes';

    protected $fillable = [
        'arca_caea_periodo_id',
        'venta_comprobante_id',
        'sucursal_id',
        'punto_venta',
        'codigo_arca',
        'numero_comprobante',
        'fecha_emision',
        'receptor_nombre',
        'doc_nro_receptor',
        'importe_total',
        'estado_rendicion',
        'informado_en',
        'request_payload_json',
        'response_payload_json',
        'observaciones_arca_json',
    ];

    protected function casts(): array
    {
        return [
            'punto_venta' => 'integer',
            'codigo_arca' => 'integer',
            'numero_comprobante' => 'integer',
            'fecha_emision' => 'date',
            'importe_total' => 'decimal:2',
            'informado_en' => 'datetime',
            'request_payload_json' => 'array',
            'response_payload_json' => 'array',
            'observaciones_arca_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(ArcaCaeaPeriodo::class, 'arca_caea_periodo_id');
    }

    public function ventaComprobante(): BelongsTo
    {
        return $this->belongsTo(VentaComprobante::class, 'venta_comprobante_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function getNumeroCompletoAttribute(): ?string
    {
        if (! $this->punto_venta || ! $this->numero_comprobante) {
            return null;
        }

        return str_pad((string) $this->punto_venta, 4, '0', STR_PAD_LEFT)
            .'-'
            .str_pad((string) $this->numero_comprobante, 8, '0', STR_PAD_LEFT);
    }

    public function getEstadoRendicionLabelAttribute(): string
    {
        return match ($this->estado_rendicion) {
            self::ESTADO_RENDICION_INFORMADO => 'Informado',
            self::ESTADO_RENDICION_OBSERVADO => 'Observado',
            self::ESTADO_RENDICION_RECHAZADO => 'Rechazado',
            default => 'Pendiente',
        };
    }
}
