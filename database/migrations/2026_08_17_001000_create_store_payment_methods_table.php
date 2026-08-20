<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('label');
            $table->string('hint')->nullable();
            $table->string('icon', 60)->default('bi-credit-card');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        $now = now();
        DB::table('store_payment_methods')->insert([
            [
                'slug' => 'cash',
                'label' => 'الدفع عند الاستلام',
                'hint' => 'ادفع كاش لمندوب التوصيل في السعودية',
                'icon' => 'bi-cash-coin',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'mada',
                'label' => 'مدى',
                'hint' => 'بطاقة مدى السعودية — يُؤكد المتجر العملية',
                'icon' => 'bi-credit-card-2-front',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'apple_pay',
                'label' => 'Apple Pay',
                'hint' => 'ادفع عبر Apple Pay — يُؤكد المتجر العملية',
                'icon' => 'bi-phone',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'stc_pay',
                'label' => 'STC Pay',
                'hint' => 'محفظة STC Pay — يُؤكد المتجر العملية',
                'icon' => 'bi-wallet2',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'card',
                'label' => 'فيزا / ماستركارد',
                'hint' => 'ادفع ببطاقة فيزا أو ماستركارد — يُؤكد المتجر العملية',
                'icon' => 'bi-credit-card',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_payment_methods');
    }
};
