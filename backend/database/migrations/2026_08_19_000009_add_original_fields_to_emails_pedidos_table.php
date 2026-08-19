<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails_pedidos', function (Blueprint $table) {
            $table->string('message_id', 500)->nullable()->after('id');
            $table->string('thread_id', 500)->nullable()->after('message_id');
            $table->string('imap_uid', 120)->nullable()->after('thread_id');
            $table->longText('raw_email')->nullable()->after('fecha_recibido');
            $table->longText('html_body_original')->nullable()->after('raw_email');
            $table->longText('text_body_original')->nullable()->after('html_body_original');
            $table->string('extraction_status', 30)->nullable()->after('datos_extraidos');

            $table->index('message_id', 'idx_emails_pedidos_message_id');
            $table->index('imap_uid', 'idx_emails_pedidos_imap_uid');
            $table->index('extraction_status', 'idx_emails_pedidos_extraction_status');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                CREATE INDEX idx_emails_pedidos_recent_history
                ON emails_pedidos (fecha_procesado_db, fecha_recibido, id)
            ');
        } else {
            Schema::table('emails_pedidos', function (Blueprint $table) {
                $table->index(['fecha_procesado_db', 'fecha_recibido', 'id'], 'idx_emails_pedidos_recent_history');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP INDEX idx_emails_pedidos_recent_history ON emails_pedidos');
        } else {
            Schema::table('emails_pedidos', function (Blueprint $table) {
                $table->dropIndex('idx_emails_pedidos_recent_history');
            });
        }

        Schema::table('emails_pedidos', function (Blueprint $table) {
            $table->dropIndex('idx_emails_pedidos_message_id');
            $table->dropIndex('idx_emails_pedidos_imap_uid');
            $table->dropIndex('idx_emails_pedidos_extraction_status');

            $table->dropColumn([
                'message_id',
                'thread_id',
                'imap_uid',
                'raw_email',
                'html_body_original',
                'text_body_original',
                'extraction_status',
            ]);
        });
    }
};
