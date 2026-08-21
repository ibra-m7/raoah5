<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('display_sections') || ! Schema::hasTable('categories')) {
            return;
        }

        $now = now();
        $sections = DB::table('display_sections')->orderBy('sort_order')->get();

        foreach ($sections as $section) {
            $root = DB::table('categories')->where('slug', $section->slug)->first();
            $firstChildId = DB::table('display_section_categories')
                ->where('display_section_id', $section->id)
                ->orderBy('sort_order')
                ->value('category_id');
            $firstChild = $firstChildId
                ? DB::table('categories')->where('id', $firstChildId)->first()
                : null;

            if ($root) {
                DB::table('categories')->where('id', $root->id)->update([
                    'parent_id' => null,
                    'name' => $section->name,
                    'sort_order' => $section->sort_order,
                    'is_active' => $section->is_active,
                    'updated_at' => $now,
                ]);
                $rootId = $root->id;
            } else {
                $rootId = DB::table('categories')->insertGetId([
                    'parent_id' => null,
                    'name' => $section->name,
                    'slug' => $section->slug,
                    'icon_url' => $firstChild->icon_url ?? $firstChild->image_url ?? '',
                    'image_url' => $firstChild->image_url ?? '',
                    'color' => $firstChild->color ?? '#88D498',
                    'sort_order' => $section->sort_order,
                    'is_active' => $section->is_active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $childIds = DB::table('display_section_categories')
                ->where('display_section_id', $section->id)
                ->orderBy('sort_order')
                ->pluck('category_id');

            foreach ($childIds as $index => $childId) {
                if ((int) $childId === (int) $rootId) {
                    continue;
                }

                DB::table('categories')->where('id', $childId)->update([
                    'parent_id' => $rootId,
                    'sort_order' => $index,
                    'updated_at' => $now,
                ]);
            }
        }

        $keepSlugs = $sections->pluck('slug')->push('electronics')->unique()->all();

        $leftoverRoots = DB::table('categories')
            ->whereNull('parent_id')
            ->whereNotIn('slug', $keepSlugs)
            ->get();

        foreach ($leftoverRoots as $old) {
            $hasChildren = DB::table('categories')->where('parent_id', $old->id)->exists();
            $hasProducts = Schema::hasTable('products')
                && DB::table('products')->where('category_id', $old->id)->exists();

            if (! $hasChildren && ! $hasProducts) {
                DB::table('categories')->where('id', $old->id)->update([
                    'is_active' => false,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Data alignment is not reversed.
    }
};
