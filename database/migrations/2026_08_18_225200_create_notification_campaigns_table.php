<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->enum('type', ['order', 'promo', 'general'])->default('promo');
            $table->string('audience', 40)->default('all_customers');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('push_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('campaign_id')
                ->nullable()
                ->after('user_id')
                ->constrained('notification_campaigns')
                ->nullOnDelete();
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->timestamp('last_used_at')->nullable()->after('platform');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropColumn('last_used_at');
        });

        Schema::dropIfExists('notification_campaigns');
    }
};
