<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE ai_conversations MODIFY user_id BIGINT UNSIGNED NULL');

        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->string('guest_token', 64)->nullable()->after('user_id');
            $table->index('guest_token');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['guest_token']);
            $table->dropColumn('guest_token');
        });

        DB::statement('ALTER TABLE ai_conversations MODIFY user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
