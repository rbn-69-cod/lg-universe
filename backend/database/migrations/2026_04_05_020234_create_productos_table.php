<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductosTable extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {

            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->decimal('precio', 8, 2);

            $table->enum('tipo', [
                'perfil',
                'miembro',
                'cuenta',
                'activacion',
                'iptv',
            ]);

            $table->integer('perfiles_por_cuenta')->default(1);
            $table->integer('duracion_dias')->default(30);

            $table->boolean('activo')->default(true);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
}
