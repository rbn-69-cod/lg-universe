<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('CREATE UNIQUE INDEX cuentas_producto_email_unique ON cuentas (producto_id, email)');
        } catch (Throwable) {
            //
        }

        try {
            DB::statement('CREATE UNIQUE INDEX perfiles_cuenta_nombre_unique ON perfiles (cuenta_id, nombre_perfil)');
        } catch (Throwable) {
            //
        }
    }

    public function down(): void
    {
        try {
            DB::statement('DROP INDEX perfiles_cuenta_nombre_unique ON perfiles');
        } catch (Throwable) {
            //
        }

        try {
            DB::statement('DROP INDEX cuentas_producto_email_unique ON cuentas');
        } catch (Throwable) {
            //
        }
    }
};
