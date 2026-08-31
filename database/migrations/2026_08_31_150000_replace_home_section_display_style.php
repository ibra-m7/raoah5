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
            $table->string('content_type', 16)->default('products')->after('key');
            $table->boolean('show_title_icon')->default(false)->after('auto_scroll_cards');
            $table->boolean('emphasize_subtitle')->default(false)->after('show_title_icon');
        });

        HomeSection::query()->each(function (HomeSection $section) {
            $style = (string) ($section->display_style ?? '');
            if ($style === '') {
                $known = ['best_prices', 'most_requested', 'fresh_groceries', 'bundle_banner'];
                $style = in_array((string) $section->key, $known, true)
                    ? (string) $section->key
                    : 'general';
            }

            $updates = [
                'content_type' => $style === 'bundle_banner' ? 'bundles' : 'products',
                'show_title_icon' => in_array($style, ['best_prices', 'most_requested'], true),
                'emphasize_subtitle' => $style === 'best_prices',
            ];

            $section->update($updates);
        });

        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn('display_style');
        });
    }

    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->string('display_style', 32)->default('general')->after('key');
        });

        HomeSection::query()->each(function (HomeSection $section) {
            $style = 'general';

            if ($section->content_type === 'bundles') {
                $style = 'bundle_banner';
            } elseif (in_array((string) $section->key, ['best_prices', 'most_requested', 'fresh_groceries'], true)) {
                $style = (string) $section->key;
            }

            $section->update(['display_style' => $style]);
        });

        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn(['content_type', 'show_title_icon', 'emphasize_subtitle']);
        });
    }
};
