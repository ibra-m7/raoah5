<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->string('title_color', 7)->nullable()->after('subtitle');
            $table->string('subtitle_color', 7)->nullable()->after('title_color');
            $table->string('background_image_url')->nullable()->after('background_color');
            $table->boolean('auto_scroll_cards')->default(false)->after('background_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn([
                'title_color',
                'subtitle_color',
                'background_image_url',
                'auto_scroll_cards',
            ]);
        });
    }
};
