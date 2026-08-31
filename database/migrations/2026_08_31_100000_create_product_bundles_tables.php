<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('bundle_price', 10, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('product_bundles')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['bundle_id', 'product_id']);
        });

        Schema::create('home_section_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bundle_id')->constrained('product_bundles')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['home_section_id', 'bundle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_section_bundles');
        Schema::dropIfExists('bundle_items');
        Schema::dropIfExists('product_bundles');
    }
};
