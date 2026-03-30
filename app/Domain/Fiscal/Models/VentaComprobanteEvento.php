<?php

namespace App\Domain\Fiscal\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaComprobanteEvento extends Model
{
    public const string TIPO_INTENCION_CREADA = 'INTENCION_CREADA';
    public const string TIPO_REFERENCIA_EXTERNA_GUARDADA = 'REFERENCIA_EXTERNA_GUARDADA';
    public const string TIPO_PENDIENTE_EMISION = 'PENDIENTE_EMISION';
    public const string TIPO_AUTORIZADO_ARCA = 'AUTORIZADO_ARCA';
    public const string TIPO_RECHAZADO_ARCA = 'RECHAZADO_ARCA';
    public const string TIPO_ERROR_EMISION = 'ERROR_EMISION';

    protected $table = 'venta_comprobante_eventos';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'venta_comprobante_id',
        'tipo_evento',
        'descripcion',
        'payload_json',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(VentaComprobante::class, 'venta_comprobante_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
