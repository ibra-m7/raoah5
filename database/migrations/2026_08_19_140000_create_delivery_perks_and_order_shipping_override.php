<?php

use App\Enums\DeliveryPerkReward;
use App\Enums\DeliveryPerkTrigger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_perks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_type', 30);
            $table->unsignedInteger('min_orders')->default(1);
            $table->string('reward_type', 20);
            $table->decimal('reward_value', 10, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        DB::table('delivery_perks')->insert([
            [
                'name' => 'بعد 4 طلبات — توصيل مجاني',
                'trigger_type' => DeliveryPerkTrigger::MinOrders->value,
                'min_orders' => 4,
                'reward_type' => DeliveryPerkReward::Free->value,
                'reward_value' => 0,
                'sort_order' => 1,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'بعد 4 طلبات — خصم 50٪ على التوصيل',
                'trigger_type' => DeliveryPerkTrigger::MinOrders->value,
                'min_orders' => 4,
                'reward_type' => DeliveryPerkReward::Percent->value,
                'reward_value' => 50,
                'sort_order' => 2,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('shipping_manual')->default(false)->after('has_free_shipping');
            $table->string('delivery_label')->nullable()->after('shipping_manual');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_manual', 'delivery_label']);
        });
        Schema::dropIfExists('delivery_perks');
    }
};
