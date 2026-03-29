<?php

namespace App\Domain\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VarianteAtributo extends Model
{
    public $timestamps = false;

    protected $table = 'variante_atributos';

    protected $fillable = [
        'variante_id',
        'atributo_id',
        'valor_id',
    ];

    public function variante(): BelongsTo
    {
        return $this->belongsTo(Variante::class, 'variante_id');
    }

    public function atributo(): BelongsTo
    {
        return $this->belongsTo(Atributo::class, 'atributo_id');
    }

    public function valor(): BelongsTo
    {
        return $this->belongsTo(AtributoValor::class, 'valor_id');
    }
}
