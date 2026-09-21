<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->constrained('cuenta')->cascadeOnDelete();
            $table->decimal('monto', 15, 2)->unsigned();
            $table->date('fecha');
            $table->string('descripcion')->nullable();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
