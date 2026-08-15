<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('venta_id');

            $table->text('datos');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas');
    }
};
