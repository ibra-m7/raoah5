<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('settings')->updateOrInsert(
            ['key' => 'delivery_hide_subtitle'],
            ['value' => '0', 'updated_at' => $now, 'created_at' => $now],
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'delivery_hide_subtitle')->delete();
    }
};
