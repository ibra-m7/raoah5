<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('banner_image_url')->nullable();
            $table->string('appbar_image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('dynamic_page_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_page_id')->constrained('dynamic_pages')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['dynamic_page_id', 'product_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE banners MODIFY link_type ENUM('product','category','url','none','page') NOT NULL DEFAULT 'none'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_page_product');
        Schema::dropIfExists('dynamic_pages');

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE banners MODIFY link_type ENUM('product','category','url','none') NOT NULL DEFAULT 'none'");
        }
    }
};
