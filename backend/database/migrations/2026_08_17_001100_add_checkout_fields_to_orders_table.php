<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 40)->nullable()->after('notes');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('coupon_code');
            $table->string('fulfillment_type', 20)->default('now')->after('discount_amount');
            $table->timestamp('scheduled_at')->nullable()->after('fulfillment_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'coupon_code',
                'discount_amount',
                'fulfillment_type',
                'scheduled_at',
            ]);
        });
    }
};
