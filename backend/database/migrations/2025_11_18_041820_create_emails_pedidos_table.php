<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails_pedidos', function (Blueprint $table) {
            $table->increments('id');                      // INT AUTO_INCREMENT PRIMARY KEY
            $table->string('destinatario_original', 255);  // VARCHAR(255)
            $table->text('asunto');                        // TEXT
            $table->string('remitente', 255)->nullable();  // VARCHAR(255)
            $table->dateTime('fecha_recibido')->nullable(); // DATETIME
            $table->longText('cuerpo_html')->nullable();   // LONGTEXT NULL
            $table->longText('datos_extraidos')->nullable(); // LONGTEXT NULL
            $table->timestamp('fecha_procesado_db')        // TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ->useCurrent();

            // índice simple por destinatario
            $table->index('destinatario_original', 'idx_destinatario_original');

            // índice por fecha_procesado_db
            $table->index('fecha_procesado_db', 'idx_fecha_procesado_db');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                CREATE INDEX idx_lookup_dest_asunto_fecha
                ON emails_pedidos (destinatario_original, asunto(191), fecha_recibido)
            ');
        } else {
            Schema::table('emails_pedidos', function (Blueprint $table) {
                $table->index(['destinatario_original', 'fecha_recibido'], 'idx_lookup_dest_fecha');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP INDEX idx_lookup_dest_asunto_fecha ON emails_pedidos');
        } else {
            Schema::table('emails_pedidos', function (Blueprint $table) {
                $table->dropIndex('idx_lookup_dest_fecha');
            });
        }

        Schema::dropIfExists('emails_pedidos');
    }
};
