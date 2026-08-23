<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dynamic_pages', function (Blueprint $table) {
            $table->string('placement', 40)->default('none')->after('is_active');
            $table->index('placement');
        });
    }

    public function down(): void
    {
        Schema::table('dynamic_pages', function (Blueprint $table) {
            $table->dropIndex(['placement']);
            $table->dropColumn('placement');
        });
    }
};
