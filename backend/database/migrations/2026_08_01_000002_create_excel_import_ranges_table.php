<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excel_import_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('plataforma');
            $table->string('hoja_excel');
            $table->unsignedInteger('fila_inicio');
            $table->unsignedInteger('fila_fin');
            $table->text('archivo_url')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_sync_at')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamps();

            $table->index(['plataforma', 'hoja_excel', 'activo']);
        });

        DB::table('excel_import_ranges')->insert([
            'plataforma' => 'Netflix Premium',
            'hoja_excel' => 'NETFLIX PREMUM',
            'fila_inicio' => 3,
            'fila_fin' => 77,
            'archivo_url' => 'https://docs.google.com/spreadsheets/d/1XOmb1vaY4ZRGDiZuINggMDaq4AthM13X/edit?usp=sharing&ouid=111245760545075131727&rtpof=true&sd=true',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('excel_import_ranges');
    }
};
