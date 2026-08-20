<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('piece_count')->nullable()->after('stock');
            $table->string('weight_label', 80)->nullable()->after('piece_count');
            $table->string('quantity_label', 120)->nullable()->after('weight_label');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['piece_count', 'weight_label', 'quantity_label']);
        });
    }
};
