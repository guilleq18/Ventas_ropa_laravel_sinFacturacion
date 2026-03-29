<?php

namespace App\Domain\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtributoValor extends Model
{
    public $timestamps = false;

    protected $table = 'atributo_valores';

    protected $fillable = [
        'atributo_id',
        'valor',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function atributo(): BelongsTo
    {
        return $this->belongsTo(Atributo::class, 'atributo_id');
    }

    public function varianteAtributos(): HasMany
    {
        return $this->hasMany(VarianteAtributo::class, 'valor_id');
    }
}
