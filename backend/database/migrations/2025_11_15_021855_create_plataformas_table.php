<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plataformas', function (Blueprint $table) {
            $table->id();

            // Nombre del servicio
            $table->string('nombre');

            // Imagen o logo
            $table->string('imagen')->nullable();

            // Precio
            $table->decimal('precio', 8, 2);

            // Características
            $table->json('features')->nullable();

            // Si está activo o no
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plataformas');
    }
};
