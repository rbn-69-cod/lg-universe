<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cuentas', 'bot_preferencia')) {
            return;
        }

        DB::table('cuentas')->update([
            'bot_preferencia' => 'principal',
            'bot_hogar_url' => null,
            'bot_temporal_url' => null,
            'bot_acceso4_url' => null,
        ]);
    }

    public function down(): void
    {
        //
    }
};
