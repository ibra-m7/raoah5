<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_smart_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('phrase', 80);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('search_trending_pins', function (Blueprint $table) {
            $table->id();
            $table->string('phrase', 80);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $smart = [
            'فطور الدوام',
            'كبسة الغدا',
            'عروض اليوم',
            'خضار وفواكه',
            'قهوة',
            'منظفات',
        ];

        foreach ($smart as $index => $phrase) {
            DB::table('search_smart_suggestions')->insert([
                'phrase' => $phrase,
                'sort_order' => $index,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_trending_pins');
        Schema::dropIfExists('search_smart_suggestions');
    }
};
