<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('producto_id');

            $table->string('email')->nullable();

            $table->string('password')->nullable();

            $table->integer('perfiles_total')->default(1);

            $table->integer('perfiles_usados')->default(0);

            $table->boolean('activo')->default(true);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
