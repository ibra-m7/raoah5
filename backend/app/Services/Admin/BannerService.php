<?php

namespace App\Services\Admin;

use App\Enums\BannerLinkType;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Support\Constants;
use App\Support\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BannerService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Banner::query()
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('title', 'like', '%'.$search.'%')
                        ->orWhere('subtitle', 'like', '%'.$search.'%');
                });
            })
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function productOptions(): Collection
    {
        return Product::query()->orderBy('name')->get(['id', 'name']);
    }

    public function categoryOptions(): Collection
    {
        return Category::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'parent_id']);
    }

    public function create(array $data): Banner
    {
        return Banner::query()->create($this->payload($data));
    }

    public function update(Banner $banner, array $data): Banner
    {
        $banner->update($this->payload($data, $banner));

        return $banner;
    }

    public function delete(Banner $banner): void
    {
        Media::delete($banner->image_url);
        $banner->delete();
    }

    private function payload(array $data, ?Banner $banner = null): array
    {
        $type = BannerLinkType::tryFrom((string) ($data['link_type'] ?? 'none')) ?? BannerLinkType::None;
        $file = $data['image'] ?? null;
        $imageUrl = trim((string) ($data['image_url'] ?? ''));

        if ($file) {
            $stored = Media::store($file, 'banners', $banner?->image_url);
        } elseif ($imageUrl !== '') {
            if ($banner && $imageUrl !== $banner->image_url) {
                Media::delete($banner->image_url);
            }
            $stored = $imageUrl;
        } else {
            $stored = $banner?->image_url;
        }

        if (! $stored) {
            throw ValidationException::withMessages([
                'image' => 'أضف صورة أو رابط صورة للإعلان.',
            ]);
        }

        return [
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'image_url' => $stored,
            'link_type' => $type,
            'link_id' => in_array($type, [BannerLinkType::Product, BannerLinkType::Category], true)
                ? ($data['link_id'] ?? null)
                : null,
            'link_url' => $type === BannerLinkType::Url ? ($data['link_url'] ?? null) : null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
