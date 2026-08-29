<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE product_relations MODIFY COLUMN type ENUM('complementary', 'upsell', 'gift') NOT NULL DEFAULT 'complementary'");
    }

    public function down(): void
    {
        DB::table('product_relations')->where('type', 'gift')->delete();
        DB::statement("ALTER TABLE product_relations MODIFY COLUMN type ENUM('complementary', 'upsell') NOT NULL DEFAULT 'complementary'");
    }
};
