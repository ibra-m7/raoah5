<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SplashScreen extends Model
{
    protected $fillable = [
        'title',
        'media_type',
        'media_url',
        'duration_ms',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'duration_ms' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderByDesc('id');
    }

    /**
     * @return array{media_type: string, media_url: string, duration_ms: int, title: ?string}|null
     */
    public function toStartupPayload(): array
    {
        return [
            'title' => $this->title,
            'media_type' => $this->media_type === 'video' ? 'video' : 'image',
            'media_url' => (string) (\App\Support\Media::url($this->media_url) ?? ''),
            'duration_ms' => max(800, (int) $this->duration_ms),
        ];
    }
}
