<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_placeholders', function (Blueprint $table) {
            $table->id();
            $table->string('phrase', 160);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $phrases = [
            'ابحث عن عروض اليوم...',
            'ابحث في روعة الخمسة',
            'في روعة تحصل كل شي روعة... أنت ابحث فقط',
            'ابحث عن كل شي روعة',
        ];

        foreach ($phrases as $index => $phrase) {
            DB::table('search_placeholders')->insert([
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
        Schema::dropIfExists('search_placeholders');
    }
};
