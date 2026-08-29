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
            $table->boolean('is_gift')->default(false)->after('is_featured');
            $table->index('is_gift');
        });

        DB::table('products')
            ->whereIn('id', function ($query) {
                $query->select('related_product_id')
                    ->from('product_relations')
                    ->where('type', 'gift');
            })
            ->update(['is_gift' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_gift']);
            $table->dropColumn('is_gift');
        });
    }
};
