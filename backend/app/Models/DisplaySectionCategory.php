<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisplaySectionCategory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'display_section_id',
        'category_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function displaySection(): BelongsTo
    {
        return $this->belongsTo(DisplaySection::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
