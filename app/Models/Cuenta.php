<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['usuario_id', 'nombre_cuenta', 'saldo'])]
class Cuenta extends Model
{
    use HasFactory;

    protected $table = 'cuenta';

    protected function casts(): array
    {
        return [
            'saldo' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'cuenta_id');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'cuenta_id');
    }

    public function deudas(): HasMany
    {
        return $this->hasMany(Deuda::class, 'cuenta_id');
    }
}
