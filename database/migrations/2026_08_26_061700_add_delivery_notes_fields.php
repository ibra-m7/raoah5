<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_rules', function (Blueprint $table) {
            $table->text('note')->nullable()->after('is_active');
            $table->boolean('note_enabled')->default(false)->after('note');
        });

        $now = now();
        DB::table('settings')->updateOrInsert(
            ['key' => 'delivery_notes_enabled'],
            ['value' => '0', 'updated_at' => $now, 'created_at' => $now],
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'delivery_general_note'],
            ['value' => '', 'updated_at' => $now, 'created_at' => $now],
        );
    }

    public function down(): void
    {
        Schema::table('delivery_rules', function (Blueprint $table) {
            $table->dropColumn(['note', 'note_enabled']);
        });
        DB::table('settings')->whereIn('key', [
            'delivery_notes_enabled',
            'delivery_general_note',
        ])->delete();
    }
};
