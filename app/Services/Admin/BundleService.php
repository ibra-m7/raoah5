<?php

namespace App\Services\Admin;

use App\Models\HomeSection;
use App\Models\ProductBundle;
use App\Support\Constants;
use App\Support\Media;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BundleService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return ProductBundle::query()
            ->withCount('items')
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function forSection(HomeSection $section): Collection
    {
        return $section->bundles()
            ->withCount('items')
            ->orderByPivot('sort_order')
            ->get()
            ->unique('id')
            ->values();
    }

    public function options(): Collection
    {
        return ProductBundle::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'bundle_price', 'discount_percent']);
    }

    /**
     * @param  array<int>|Collection<int, int>|list<int>  $ids
     */
    public function pickerItems(array|Collection $ids): Collection
    {
        if ($ids instanceof Collection) {
            $ids = $ids->all();
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            return collect();
        }

        return ProductBundle::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'bundle_price', 'discount_percent', 'image_url']);
    }

    public function createForSection(HomeSection $section, array $data): ProductBundle
    {
        $bundle = ProductBundle::query()->create($this->payload($data));
        $this->syncItems($bundle, $data['items'] ?? []);
        $this->refreshPricing($bundle);
        $this->attachToSection($section, $bundle, isset($data['sort_order']) ? (int) $data['sort_order'] : null);

        return $bundle->fresh(['items.product']);
    }

    public function update(ProductBundle $bundle, array $data): ProductBundle
    {
        $bundle->update($this->payload($data, $bundle));
        $this->syncItems($bundle, $data['items'] ?? []);
        $this->refreshPricing($bundle);

        return $bundle->fresh(['items.product']);
    }

    public function delete(ProductBundle $bundle): void
    {
        Media::delete($bundle->image_url);
        $bundle->items()->delete();
        $bundle->homeSections()->detach();
        $bundle->delete();
    }

    /**
     * @param  list<int>  $bundleIds
     */
    public function reorderInSection(HomeSection $section, array $bundleIds): void
    {
        $sync = [];
        foreach (array_values(array_filter(array_map('intval', $bundleIds))) as $i => $id) {
            $sync[$id] = ['sort_order' => $i];
        }

        $section->bundles()->sync($sync);
    }

    private function attachToSection(HomeSection $section, ProductBundle $bundle, ?int $sortOrder = null): void
    {
        if ($sortOrder === null) {
            $max = (int) $section->bundles()->max('home_section_bundles.sort_order');
            $sortOrder = $max + 1;
        }

        $section->bundles()->syncWithoutDetaching([
            $bundle->id => ['sort_order' => $sortOrder],
        ]);
    }

    private function payload(array $data, ?ProductBundle $bundle = null): array
    {
        return [
            'name' => $data['name'],
            'slug' => Slug::unique(
                $data['slug'] ?? $data['name'],
                'product_bundles',
                'slug',
                $bundle?->id,
            ),
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'] ?? null,
            'image_url' => $this->storeImage(
                $data['image'] ?? null,
                $data['image_url'] ?? '',
                $bundle?->image_url,
            ),
            'discount_percent' => round((float) ($data['discount_percent'] ?? 0), 2),
            'bundle_price' => round((float) ($data['bundle_price'] ?? 0), 2),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function storeImage(mixed $file, string $url, ?string $current): ?string
    {
        $url = trim($url);
        if ($file) {
            return Media::store($file, 'bundles', $current);
        }
        if ($url !== '') {
            if ($current && $url !== $current) {
                Media::delete($current);
            }

            return $url;
        }

        return $current;
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    private function syncItems(ProductBundle $bundle, array $items): void
    {
        $bundle->items()->delete();

        foreach (array_values($items) as $i => $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $bundle->items()->create([
                'product_id' => $productId,
                'quantity' => max(1, min(99, (int) ($row['quantity'] ?? 1))),
                'sort_order' => $i,
            ]);
        }
    }

    private function refreshPricing(ProductBundle $bundle): void
    {
        $bundle->loadMissing(['items.product']);
        $original = $bundle->computeOriginalPrice();

        if ($original <= 0) {
            return;
        }

        $discountPercent = (float) $bundle->discount_percent;
        $bundlePrice = (float) $bundle->bundle_price;

        if ($discountPercent > 0 && $bundlePrice <= 0) {
            $bundlePrice = round($original * (1 - $discountPercent / 100), 2);
        } elseif ($bundlePrice > 0 && $discountPercent <= 0 && $bundlePrice < $original) {
            $discountPercent = round((1 - $bundlePrice / $original) * 100, 2);
        } elseif ($discountPercent > 0 && $bundlePrice > 0) {
            $bundlePrice = round($original * (1 - $discountPercent / 100), 2);
        }

        $bundlePrice = min($bundlePrice, $original);
        $bundlePrice = max(0, $bundlePrice);

        if ($discountPercent <= 0 && $bundlePrice < $original) {
            $discountPercent = round((1 - $bundlePrice / $original) * 100, 2);
        }

        $bundle->update([
            'bundle_price' => $bundlePrice,
            'discount_percent' => max(0, $discountPercent),
        ]);
    }
}
