<?php

namespace App\Domain\Fiscal\Models;

use App\Domain\Core\Models\Sucursal;
use App\Domain\Ventas\Models\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VentaComprobante extends Model
{
    public const string MODO_ELECTRONICA_ARCA = 'ELECTRONICA_ARCA';
    public const string MODO_EXTERNA_REFERENCIADA = 'EXTERNA_REFERENCIADA';
    public const string MODO_INTERNA_NO_FISCAL = 'INTERNA_NO_FISCAL';

    public const string ESTADO_BORRADOR = 'BORRADOR';
    public const string ESTADO_PENDIENTE = 'PENDIENTE';
    public const string ESTADO_AUTORIZADO = 'AUTORIZADO';
    public const string ESTADO_RECHAZADO = 'RECHAZADO';
    public const string ESTADO_REFERENCIADO = 'REFERENCIADO';
    public const string ESTADO_ANULADO = 'ANULADO';

    public const string TIPO_FACTURA = 'FACTURA';
    public const string TIPO_NOTA_CREDITO = 'NC';
    public const string TIPO_NOTA_DEBITO = 'ND';

    protected $table = 'venta_comprobantes';

    protected $fillable = [
        'venta_id',
        'sucursal_id',
        'modo_emision',
        'estado',
        'tipo_comprobante',
        'clase',
        'codigo_arca',
        'punto_venta',
        'numero_comprobante',
        'fecha_emision',
        'moneda',
        'cotizacion_moneda',
        'doc_tipo_receptor',
        'doc_nro_receptor',
        'receptor_nombre',
        'receptor_condicion_iva',
        'receptor_domicilio',
        'importe_neto',
        'importe_iva',
        'importe_otros_tributos',
        'importe_total',
        'cae',
        'cae_vto',
        'qr_payload_json',
        'qr_url',
        'referencia_externa_tipo',
        'referencia_externa_numero',
        'resultado_arca',
        'observaciones_arca_json',
        'request_payload_json',
        'response_payload_json',
        'emitido_en',
    ];

    protected function casts(): array
    {
        return [
            'codigo_arca' => 'integer',
            'punto_venta' => 'integer',
            'numero_comprobante' => 'integer',
            'fecha_emision' => 'datetime',
            'cotizacion_moneda' => 'decimal:6',
            'doc_tipo_receptor' => 'integer',
            'importe_neto' => 'decimal:2',
            'importe_iva' => 'decimal:2',
            'importe_otros_tributos' => 'decimal:2',
            'importe_total' => 'decimal:2',
            'cae_vto' => 'date',
            'qr_payload_json' => 'array',
            'observaciones_arca_json' => 'array',
            'request_payload_json' => 'array',
            'response_payload_json' => 'array',
            'emitido_en' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(VentaComprobanteEvento::class, 'venta_comprobante_id');
    }

    public function caeaComprobantes(): HasMany
    {
        return $this->hasMany(ArcaCaeaComprobante::class, 'venta_comprobante_id');
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

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_AUTORIZADO => 'Autorizado',
            self::ESTADO_RECHAZADO => 'Rechazado',
            self::ESTADO_REFERENCIADO => 'Referenciado',
            self::ESTADO_ANULADO => 'Anulado',
            default => 'Pendiente',
        };
    }

    public function getDescripcionCompletaAttribute(): string
    {
        $base = match ($this->tipo_comprobante) {
            self::TIPO_NOTA_CREDITO => 'Nota de crédito',
            self::TIPO_NOTA_DEBITO => 'Nota de débito',
            default => 'Factura',
        };

        return trim($base.' '.($this->clase ?: ''));
    }

    public function getEsImprimibleAttribute(): bool
    {
        return $this->modo_emision === self::MODO_ELECTRONICA_ARCA
            && $this->estado === self::ESTADO_AUTORIZADO;
    }
}
