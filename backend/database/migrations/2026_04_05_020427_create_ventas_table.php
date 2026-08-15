<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('producto_id');

            $table->foreignId('cliente_id');

            $table->foreignId('cuenta_id')->nullable();

            $table->foreignId('perfil_id')->nullable();

            $table->decimal('precio', 8, 2);

            $table->date('inicio');

            $table->date('fin');

            $table->enum('estado', [
                'pendiente',
                'pagado',
                'entregado',
                'cancelado',
            ])->default('pendiente');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
