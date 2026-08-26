<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'query',
        'matched_product_id',
        'results_count',
        'source',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'matched_product_id');
    }

    public function hasProductMatch(): bool
    {
        return $this->matched_product_id !== null || $this->results_count > 0;
    }
}
