<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles', function (Blueprint $table) {

            $table->id();

            $table->foreignId('cuenta_id');

            $table->string('nombre_perfil');

            $table->string('pin')->nullable();

            $table->boolean('ocupado')->default(false);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};
