<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('splash_screens', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('media_type', 20)->default('image'); // image|video
            $table->string('media_url');
            $table->unsignedInteger('duration_ms')->default(2500);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('splash_screens');
    }
};
