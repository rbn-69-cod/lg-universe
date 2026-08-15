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

        DB::table('cuentas')
            ->where(function ($query) {
                $query->whereNull('bot_hogar_url')
                    ->orWhere('bot_hogar_url', '');
            })
            ->where(function ($query) {
                $query->whereNull('bot_temporal_url')
                    ->orWhere('bot_temporal_url', '');
            })
            ->where(function ($query) {
                $query->whereNull('bot_acceso4_url')
                    ->orWhere('bot_acceso4_url', '');
            })
            ->update(['bot_preferencia' => 'principal']);
    }

    public function down(): void
    {
        //
    }
};
