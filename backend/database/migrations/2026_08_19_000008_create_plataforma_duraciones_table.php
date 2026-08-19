<?php

use App\Models\Plataforma;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plataforma_duraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plataforma_id')->constrained('plataformas')->cascadeOnDelete();
            $table->unsignedTinyInteger('duracion_meses');
            $table->decimal('precio', 8, 2);
            $table->boolean('activo')->default(false);
            $table->timestamps();

            $table->unique(['plataforma_id', 'duracion_meses'], 'uniq_plataforma_duracion');
            $table->index(['activo', 'duracion_meses'], 'idx_plataforma_duraciones_active_months');
        });

        Plataforma::query()->orderBy('id')->get()->each(function (Plataforma $plataforma): void {
            DB::table('plataforma_duraciones')->insert([
                'plataforma_id' => $plataforma->id,
                'duracion_meses' => 1,
                'precio' => $plataforma->precio,
                'activo' => (bool) $plataforma->activo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plataforma_duraciones');
    }
};
