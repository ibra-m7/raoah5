<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('promo_type', 20)->nullable()->after('discount_price');
        });

        DB::table('products')
            ->whereNotNull('discount_price')
            ->whereColumn('discount_price', '<', 'price')
            ->whereNull('promo_type')
            ->update(['promo_type' => 'discount']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('promo_type');
        });
    }
};
