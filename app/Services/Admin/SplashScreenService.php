<?php

namespace App\Services\Admin;

use App\Models\SplashScreen;
use App\Support\Constants;
use App\Support\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SplashScreenService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return SplashScreen::query()
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function create(array $data, ?UploadedFile $file = null): SplashScreen
    {
        return DB::transaction(function () use ($data, $file) {
            $payload = $this->payload($data, null, $file);
            $splash = SplashScreen::query()->create($payload);
            if ($splash->is_active) {
                $this->deactivateOthers($splash->id);
            }

            return $splash;
        });
    }

    public function update(SplashScreen $splash, array $data, ?UploadedFile $file = null): SplashScreen
    {
        return DB::transaction(function () use ($splash, $data, $file) {
            $splash->update($this->payload($data, $splash, $file));
            if ($splash->is_active) {
                $this->deactivateOthers($splash->id);
            }

            return $splash->fresh();
        });
    }

    public function delete(SplashScreen $splash): void
    {
        Media::delete($splash->media_url);
        $splash->delete();
    }

    /**
     * @return array{title: ?string, media_type: string, media_url: string, duration_ms: int, sort_order: int, is_active: bool}
     */
    private function payload(array $data, ?SplashScreen $splash, ?UploadedFile $file): array
    {
        $type = ($data['media_type'] ?? 'image') === 'video' ? 'video' : 'image';
        $urlInput = trim((string) ($data['media_url'] ?? ''));
        $stored = $splash?->media_url;

        if ($file !== null) {
            $ext = strtolower($file->getClientOriginalExtension());
            $ok = $type === 'video'
                ? in_array($ext, ['mp4', 'mov', 'webm', 'm4v'], true)
                : in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
            if (! $ok) {
                throw ValidationException::withMessages([
                    'media_file' => $type === 'video'
                        ? 'ملف الفيديو يجب أن يكون mp4 أو mov أو webm.'
                        : 'ملف الصورة غير مدعوم.',
                ]);
            }
            $stored = Media::store($file, 'splash', $splash?->media_url);
        } elseif ($urlInput !== '') {
            if ($splash && $urlInput !== $splash->media_url) {
                Media::delete($splash->media_url);
            }
            $stored = $urlInput;
        }

        if (! $stored) {
            throw ValidationException::withMessages([
                'media_file' => 'أضف صورة أو فيديو أو رابط وسائط لشاشة البداية.',
            ]);
        }

        return [
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'media_type' => $type,
            'media_url' => $stored,
            'duration_ms' => max(800, (int) ($data['duration_ms'] ?? 2500)),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function deactivateOthers(int $keepId): void
    {
        SplashScreen::query()
            ->where('id', '!=', $keepId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
