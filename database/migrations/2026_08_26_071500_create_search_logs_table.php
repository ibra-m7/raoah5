<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('query', 191);
            $table->foreignId('matched_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('results_count')->default(0);
            $table->string('source', 32)->default('app');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['query', 'created_at']);
            $table->index('matched_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
