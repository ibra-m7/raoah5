<?php

namespace App\Services\Admin;

use App\Models\HomeSection;
use App\Support\Constants;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HomeSectionService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return HomeSection::query()
            ->withCount('products')
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('title', 'like', '%'.$search.'%')
                        ->orWhere('key', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('sort_order')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function create(array $data): HomeSection
    {
        $section = HomeSection::query()->create($this->payload($data));
        $this->syncProducts($section, $data['product_ids'] ?? []);

        return $section;
    }

    public function update(HomeSection $section, array $data): HomeSection
    {
        $section->update($this->payload($data, $section));
        $this->syncProducts($section, $data['product_ids'] ?? []);

        return $section;
    }

    public function delete(HomeSection $section): void
    {
        $section->products()->detach();
        $section->delete();
    }

    private function payload(array $data, ?HomeSection $section = null): array
    {
        $style = (string) ($data['display_style'] ?? 'general');
        $known = ['best_prices', 'most_requested', 'fresh_groceries'];
        if (in_array($style, $known, true)) {
            $key = $style;
        } elseif ($section && $section->displayStyle() === 'general') {
            $key = $section->key ?: Slug::unique($data['title'], 'home_sections', 'key', $section->id);
        } else {
            $key = Slug::unique($data['title'], 'home_sections', 'key', $section?->id);
        }

        return [
            'key' => $key,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'background_color' => $this->normalizeBackgroundColor($data['background_color'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function syncProducts(HomeSection $section, array $ids): void
    {
        $sync = [];
        foreach (array_values(array_filter($ids)) as $i => $id) {
            $sync[(int) $id] = ['sort_order' => $i];
        }
        $section->products()->sync($sync);
    }

    private function normalizeBackgroundColor(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $hex = ltrim($value, '#');
        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return null;
        }

        return '#'.strtoupper($hex);
    }
}
