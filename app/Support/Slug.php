<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class Slug
{
    public static function unique(string $source, string $table, string $column = 'slug', ?int $ignoreId = null): string
    {
        $base = self::from($source);
        $slug = $base;
        $i = 2;

        while (self::exists($table, $column, $slug, $ignoreId)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public static function from(string $source): string
    {
        $slug = (string) Str::of($source)
            ->trim()
            ->lower()
            ->replaceMatches('/\s+/u', '-')
            ->replaceMatches('/[^\p{L}\p{N}\-]+/u', '')
            ->replaceMatches('/-+/', '-')
            ->trim('-');

        return $slug !== '' ? $slug : 'item-'.Str::lower(Str::random(6));
    }

    private static function exists(string $table, string $column, string $slug, ?int $ignoreId): bool
    {
        $query = DB::table($table)->where($column, $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
