<?php

namespace App\Services\Admin;

use App\Enums\DynamicPagePlacement;
use App\Models\DynamicPage;
use App\Models\Product;
use App\Support\Constants;
use App\Support\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DynamicPageService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return DynamicPage::query()
            ->withCount('products')
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function productOptions(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'discount_price']);
    }

    public function options(): Collection
    {
        return DynamicPage::query()->orderBy('title')->get(['id', 'title']);
    }

    public function create(array $data): DynamicPage
    {
        $page = DynamicPage::query()->create($this->payload($data));
        $this->syncProducts($page, $data['product_ids'] ?? []);

        return $page;
    }

    public function update(DynamicPage $page, array $data): DynamicPage
    {
        $page->update($this->payload($data, $page));
        $this->syncProducts($page, $data['product_ids'] ?? []);

        return $page;
    }

    public function delete(DynamicPage $page): void
    {
        Media::delete($page->banner_image_url);
        Media::delete($page->appbar_image_url);
        $page->products()->detach();
        $page->delete();
    }

    private function payload(array $data, ?DynamicPage $page = null): array
    {
        return [
            'title' => $data['title'],
            'banner_image_url' => $this->storeImage(
                $data['banner_image'] ?? null,
                $data['banner_image_url'] ?? '',
                $page?->banner_image_url,
            ),
            'appbar_image_url' => $this->storeImage(
                $data['appbar_image'] ?? null,
                $data['appbar_image_url'] ?? '',
                $page?->appbar_image_url,
            ),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'placement' => DynamicPagePlacement::tryFrom((string) ($data['placement'] ?? ''))
                ?? DynamicPagePlacement::None,
        ];
    }

    private function storeImage(mixed $file, string $url, ?string $current): ?string
    {
        $url = trim($url);
        if ($file) {
            return Media::store($file, 'dynamic-pages', $current);
        }
        if ($url !== '') {
            if ($current && $url !== $current) {
                Media::delete($current);
            }

            return $url;
        }

        return $current;
    }

    private function syncProducts(DynamicPage $page, array $ids): void
    {
        $sync = [];
        foreach (array_values(array_filter($ids)) as $i => $id) {
            $sync[(int) $id] = ['sort_order' => $i];
        }
        $page->products()->sync($sync);
    }
}
