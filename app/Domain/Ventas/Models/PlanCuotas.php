<?php

namespace App\Domain\Ventas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanCuotas extends Model
{
    public $timestamps = false;

    protected $table = 'plan_cuotas';

    protected $fillable = [
        'tarjeta',
        'cuotas',
        'recargo_pct',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'cuotas' => 'integer',
            'recargo_pct' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'plan_id');
    }
}
