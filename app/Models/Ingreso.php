<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cuenta_id', 'monto', 'fecha', 'descripcion', 'categoria_id'])]
class Ingreso extends Model
{
    protected $table = 'ingresos';

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
