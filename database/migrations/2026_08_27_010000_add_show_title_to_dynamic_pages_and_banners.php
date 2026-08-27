<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dynamic_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('dynamic_pages', 'show_title')) {
                $table->boolean('show_title')->default(false)->after('title');
            }
        });

        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'show_title')) {
                $table->boolean('show_title')->default(false)->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dynamic_pages', function (Blueprint $table) {
            if (Schema::hasColumn('dynamic_pages', 'show_title')) {
                $table->dropColumn('show_title');
            }
        });

        Schema::table('banners', function (Blueprint $table) {
            if (Schema::hasColumn('banners', 'show_title')) {
                $table->dropColumn('show_title');
            }
        });
    }
};
