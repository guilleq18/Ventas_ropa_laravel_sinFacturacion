<?php

namespace App\Domain\Fiscal\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArcaCaeaPeriodo extends Model
{
    public const string ENTORNO_HOMOLOGACION = 'HOMOLOGACION';
    public const string ENTORNO_PRODUCCION = 'PRODUCCION';

    public const string ESTADO_SOLICITUD_AUTORIZADO = 'AUTORIZADO';
    public const string ESTADO_SOLICITUD_OBSERVADO = 'OBSERVADO';
    public const string ESTADO_SOLICITUD_ERROR = 'ERROR';

    public const string ESTADO_INFORMACION_PENDIENTE = 'PENDIENTE';
    public const string ESTADO_INFORMACION_PARCIAL = 'PARCIAL';
    public const string ESTADO_INFORMACION_COMPLETA = 'COMPLETA';
    public const string ESTADO_INFORMACION_SIN_MOVIMIENTO = 'SIN_MOVIMIENTO';
    public const string ESTADO_INFORMACION_VENCIDO = 'VENCIDO';

    protected $table = 'arca_caea_periodos';

    protected $fillable = [
        'entorno',
        'cuit_representada',
        'periodo',
        'orden',
        'caea',
        'estado_solicitud',
        'estado_informacion',
        'vigente_desde',
        'vigente_hasta',
        'fecha_tope_informar',
        'fecha_proceso',
        'comprobantes_informados',
        'ultimo_informado_en',
        'sin_movimiento_informado_en',
        'ultimo_synced_at',
        'request_payload_json',
        'response_payload_json',
        'observaciones_arca_json',
    ];

    protected function casts(): array
    {
        return [
            'periodo' => 'integer',
            'orden' => 'integer',
            'comprobantes_informados' => 'integer',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
            'fecha_tope_informar' => 'date',
            'fecha_proceso' => 'date',
            'ultimo_informado_en' => 'datetime',
            'sin_movimiento_informado_en' => 'datetime',
            'ultimo_synced_at' => 'datetime',
            'request_payload_json' => 'array',
            'response_payload_json' => 'array',
            'observaciones_arca_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(ArcaCaeaComprobante::class, 'arca_caea_periodo_id');
    }

    public function getClavePeriodoAttribute(): string
    {
        return sprintf('%06d-%d', (int) $this->periodo, (int) $this->orden);
    }

    public function getPeriodoLabelAttribute(): string
    {
        $raw = sprintf('%06d', (int) $this->periodo);
        $year = substr($raw, 0, 4);
        $month = substr($raw, 4, 2);

        return "{$month}/{$year}";
    }

    public function getOrdenLabelAttribute(): string
    {
        return match ((int) $this->orden) {
            1 => '1ra quincena',
            2 => '2da quincena',
            default => 'Quincena '.$this->orden,
        };
    }

    public function getEstadoSolicitudLabelAttribute(): string
    {
        return match ($this->estado_solicitud) {
            self::ESTADO_SOLICITUD_AUTORIZADO => 'Autorizado',
            self::ESTADO_SOLICITUD_OBSERVADO => 'Observado',
            self::ESTADO_SOLICITUD_ERROR => 'Error',
            default => 'Pendiente',
        };
    }

    public function getEstadoInformacionLabelAttribute(): string
    {
        return match ($this->estado_informacion) {
            self::ESTADO_INFORMACION_PARCIAL => 'Parcial',
            self::ESTADO_INFORMACION_COMPLETA => 'Completa',
            self::ESTADO_INFORMACION_SIN_MOVIMIENTO => 'Sin movimiento',
            self::ESTADO_INFORMACION_VENCIDO => 'Vencido',
            default => 'Pendiente',
        };
    }

    public function getRangoVigenciaLabelAttribute(): string
    {
        if (! $this->vigente_desde || ! $this->vigente_hasta) {
            return '-';
        }

        return $this->vigente_desde->format('d/m/Y').' al '.$this->vigente_hasta->format('d/m/Y');
    }

    public function getEstaVigenteAttribute(): bool
    {
        if (! $this->vigente_desde || ! $this->vigente_hasta) {
            return false;
        }

        $today = CarbonImmutable::today();

        return $today->betweenIncluded(
            CarbonImmutable::parse($this->vigente_desde),
            CarbonImmutable::parse($this->vigente_hasta),
        );
    }

    public function getInformacionVencidaAttribute(): bool
    {
        if (! $this->fecha_tope_informar) {
            return false;
        }

        if (! in_array($this->estado_informacion, [
            self::ESTADO_INFORMACION_PENDIENTE,
            self::ESTADO_INFORMACION_PARCIAL,
        ], true)) {
            return false;
        }

        return CarbonImmutable::today()->greaterThan(CarbonImmutable::parse($this->fecha_tope_informar));
    }

    public function getResumenRendicionAttribute(): array
    {
        $comprobantes = $this->relationLoaded('comprobantes')
            ? $this->comprobantes
            : collect();

        return [
            'total' => (int) ($this->comprobantes_count ?? $comprobantes->count()),
            'informados' => (int) ($this->comprobantes_informados_count ?? $comprobantes->where('estado_rendicion', ArcaCaeaComprobante::ESTADO_RENDICION_INFORMADO)->count()),
            'pendientes' => (int) ($this->comprobantes_pendientes_count ?? $comprobantes->where('estado_rendicion', ArcaCaeaComprobante::ESTADO_RENDICION_PENDIENTE)->count()),
            'observados' => (int) ($this->comprobantes_observados_count ?? $comprobantes->where('estado_rendicion', ArcaCaeaComprobante::ESTADO_RENDICION_OBSERVADO)->count()),
            'rechazados' => (int) ($this->comprobantes_rechazados_count ?? $comprobantes->where('estado_rendicion', ArcaCaeaComprobante::ESTADO_RENDICION_RECHAZADO)->count()),
        ];
    }
}
