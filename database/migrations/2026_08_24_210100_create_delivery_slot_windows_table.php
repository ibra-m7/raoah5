<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_slot_windows', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['weekday', 'is_active', 'sort_order']);
        });

        $now = now();
        $windows = [
            ['10:00:00', '12:00:00'],
            ['12:00:00', '14:00:00'],
            ['14:00:00', '16:00:00'],
            ['16:00:00', '18:00:00'],
            ['18:00:00', '21:00:00'],
        ];
        $rows = [];
        foreach (range(0, 6) as $weekday) {
            foreach ($windows as $index => [$start, $end]) {
                $rows[] = [
                    'weekday' => $weekday,
                    'start_time' => $start,
                    'end_time' => $end,
                    'sort_order' => $index,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        DB::table('delivery_slot_windows')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_slot_windows');
    }
};
