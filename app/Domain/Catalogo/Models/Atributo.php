<?php

namespace App\Domain\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atributo extends Model
{
    public $timestamps = false;

    protected $table = 'atributos';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function valores(): HasMany
    {
        return $this->hasMany(AtributoValor::class, 'atributo_id');
    }

    public function varianteAtributos(): HasMany
    {
        return $this->hasMany(VarianteAtributo::class, 'atributo_id');
    }
}
