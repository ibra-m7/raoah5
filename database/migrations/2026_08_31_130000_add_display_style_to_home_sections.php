<?php

use App\Models\HomeSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->string('display_style', 32)->default('general')->after('key');
        });

        $known = ['best_prices', 'most_requested', 'fresh_groceries', 'bundle_banner'];

        HomeSection::query()->each(function (HomeSection $section) use ($known) {
            $style = in_array((string) $section->key, $known, true)
                ? (string) $section->key
                : 'general';

            $section->update(['display_style' => $style]);
        });

        if (Schema::hasColumn('product_bundles', 'show_on_home')) {
            Schema::table('product_bundles', function (Blueprint $table) {
                $table->dropColumn('show_on_home');
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_bundles', function (Blueprint $table) {
            $table->boolean('show_on_home')->default(true)->after('is_active');
        });

        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn('display_style');
        });
    }
};
