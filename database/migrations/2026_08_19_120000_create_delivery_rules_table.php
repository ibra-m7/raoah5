<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min_km', 8, 2)->default(0);
            $table->decimal('max_km', 8, 2)->nullable();
            $table->string('pricing_type', 20);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('per_km_mode', 20)->default('entire');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('delivery_rules')->insert([
            [
                'name' => 'أقل من 10 كم',
                'min_km' => 0,
                'max_km' => 10,
                'pricing_type' => 'free',
                'amount' => 0,
                'per_km_mode' => 'entire',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'أكثر من 10 كم',
                'min_km' => 10,
                'max_km' => null,
                'pricing_type' => 'per_km',
                'amount' => 1,
                'per_km_mode' => 'entire',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $now = now();
        $settings = [
            'delivery_enabled' => '1',
            'delivery_first_order_free' => '1',
            'delivery_store_lat' => '',
            'delivery_store_lng' => '',
            'delivery_store_address' => '',
            'delivery_max_km' => '',
            'delivery_fallback_fee' => '15',
        ];
        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_rules');
    }
};
