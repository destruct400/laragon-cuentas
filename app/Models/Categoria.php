<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre_categoria'])]
class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'categoria_id');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'categoria_id');
    }

    public function deudas(): HasMany
    {
        return $this->hasMany(Deuda::class, 'categoria_id');
    }
}
