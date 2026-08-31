<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_method', 20)->default('delivery')->after('status');
        });

        Schema::table('couriers', function (Blueprint $table) {
            $table->boolean('handles_delivery')->default(true)->after('is_online');
            $table->boolean('handles_pickup')->default(false)->after('handles_delivery');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_method');
        });

        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn(['handles_delivery', 'handles_pickup']);
        });
    }
};
