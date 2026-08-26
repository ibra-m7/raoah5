<?php

namespace App\Services\Admin;

use App\Models\OnboardingSlide;
use App\Support\Constants;
use App\Support\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class OnboardingSlideService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return OnboardingSlide::query()
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('title', 'like', '%'.$search.'%')
                        ->orWhere('subtitle', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function create(array $data, ?UploadedFile $image = null): OnboardingSlide
    {
        return OnboardingSlide::query()->create($this->payload($data, null, $image));
    }

    public function update(OnboardingSlide $slide, array $data, ?UploadedFile $image = null): OnboardingSlide
    {
        $slide->update($this->payload($data, $slide, $image));

        return $slide->fresh();
    }

    public function delete(OnboardingSlide $slide): void
    {
        Media::delete($slide->image_url);
        $slide->delete();
    }

    /**
     * @return array{title: string, subtitle: ?string, description: ?string, image_url: ?string, sort_order: int, is_active: bool}
     */
    private function payload(array $data, ?OnboardingSlide $slide, ?UploadedFile $image): array
    {
        $urlInput = trim((string) ($data['image_url'] ?? ''));
        $stored = $slide?->image_url;

        if ($image !== null) {
            $stored = Media::store($image, 'onboarding', $slide?->image_url);
        } elseif ($urlInput !== '') {
            if ($slide && $urlInput !== $slide->image_url) {
                Media::delete($slide->image_url);
            }
            $stored = $urlInput;
        } elseif (array_key_exists('image_url', $data) && $urlInput === '' && ! $image) {
            // keep existing unless explicitly cleared via empty and no file — keep existing
            $stored = $slide?->image_url;
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => 'عنوان الشريحة مطلوب.',
            ]);
        }

        return [
            'title' => $title,
            'subtitle' => trim((string) ($data['subtitle'] ?? '')) ?: null,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'image_url' => $stored,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
