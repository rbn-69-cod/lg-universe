<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iptv_cuentas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('producto_id');

            $table->string('usuario');

            $table->string('password');

            $table->string('url');

            $table->boolean('entregado')->default(false);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iptv_cuentas');
    }
};
