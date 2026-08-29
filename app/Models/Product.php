<?php

namespace App\Models;

use App\Enums\ProductRelationType;
use App\Enums\PromoType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'barcode',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'promo_type',
        'stock',
        'piece_count',
        'weight_label',
        'quantity_label',
        'rating',
        'review_count',
        'benefits',
        'keywords',
        'usage_instructions',
        'is_active',
        'is_featured',
        'is_gift',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'promo_type' => PromoType::class,
            'stock' => 'integer',
            'piece_count' => 'integer',
            'rating' => 'decimal:2',
            'review_count' => 'integer',
            'benefits' => 'array',
            'keywords' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_gift' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function productRelations(): HasMany
    {
        return $this->hasMany(ProductRelation::class);
    }

    public function complementaryProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_relations', 'product_id', 'related_product_id')
            ->withPivot('type', 'sort_order')
            ->wherePivot('type', ProductRelationType::Complementary->value)
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function upsellProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_relations', 'product_id', 'related_product_id')
            ->withPivot('type', 'sort_order')
            ->wherePivot('type', ProductRelationType::Upsell->value)
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function giftProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_relations', 'product_id', 'related_product_id')
            ->withPivot('type', 'sort_order')
            ->wherePivot('type', ProductRelationType::Gift->value)
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->limit(1);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function homeSections(): BelongsToMany
    {
        return $this->belongsToMany(HomeSection::class, 'home_section_products')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeGift(Builder $query): Builder
    {
        return $query->where('is_gift', true);
    }

    public function scopeSellable(Builder $query): Builder
    {
        return $query->where('is_gift', false);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $nested) use ($like, $term) {
            $nested->where('name', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('barcode', 'like', $like)
                ->orWhere('keywords', 'like', $like)
                ->orWhere('benefits', 'like', $like);

            if (preg_match('/^\d+$/', $term) === 1) {
                $nested->orWhere('id', (int) $term);
            }
        });
    }

    public function scopeForCategory(Builder $query, int|string|null $categoryId): Builder
    {
        if ($categoryId === null || $categoryId === '') {
            return $query;
        }

        $ids = Category::query()
            ->where('id', $categoryId)
            ->orWhere('parent_id', $categoryId)
            ->pluck('id');

        $frontier = $ids;
        while ($frontier->isNotEmpty()) {
            $children = Category::query()->whereIn('parent_id', $frontier)->pluck('id');
            $frontier = $children->diff($ids)->values();
            $ids = $ids->merge($frontier)->unique()->values();
        }

        return $query->whereIn('category_id', $ids);
    }

    public function displayQuantity(): string
    {
        $custom = trim((string) $this->quantity_label);
        if ($custom !== '') {
            return $custom;
        }

        $parts = [];
        if ($this->piece_count) {
            $parts[] = $this->piece_count.' قطعة';
        }
        $weight = trim((string) $this->weight_label);
        if ($weight !== '') {
            $parts[] = $weight;
        }

        return implode(' · ', $parts);
    }

    protected function effectivePrice(): Attribute
    {
        return Attribute::get(fn () => $this->discount_price ?? $this->price);
    }

    protected function hasDiscount(): Attribute
    {
        return Attribute::get(
            fn () => $this->discount_price !== null && $this->discount_price < $this->price
        );
    }

    protected function discountPercent(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->has_discount || (float) $this->price <= 0) {
                return 0;
            }

            return (int) round((1 - ((float) $this->discount_price / (float) $this->price)) * 100);
        });
    }

    protected function isAvailable(): Attribute
    {
        return Attribute::get(fn () => $this->is_active && $this->stock > 0);
    }

    public function scopeOnPromo(Builder $query, ?PromoType $type = null): Builder
    {
        $query->whereNotNull('discount_price')->whereColumn('discount_price', '<', 'price');

        if ($type) {
            $query->where('promo_type', $type);
        }

        return $query;
    }

    public function scopeWithoutPromo(Builder $query, ?int $exceptId = null): Builder
    {
        return $query->where(function (Builder $inner) use ($exceptId) {
            $inner->whereNull('discount_price')
                ->orWhereColumn('discount_price', '>=', 'price');

            if ($exceptId) {
                $inner->orWhereKey($exceptId);
            }
        });
    }
}
