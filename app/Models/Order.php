<?php

namespace App\Models;

use App\Enums\OrderMethod;
use App\Enums\OrderStatus;
use App\Support\DeliverySettings;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'courier_id',
        'address_id',
        'order_number',
        'status',
        'order_method',
        'subtotal',
        'shipping_fee',
        'total',
        'has_free_shipping',
        'shipping_manual',
        'delivery_label',
        'payment_method',
        'payment_status',
        'shipping_name',
        'shipping_phone',
        'shipping_city',
        'shipping_district',
        'shipping_street',
        'shipping_details',
        'notes',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'fulfillment_type',
        'scheduled_at',
        'cancelled_by',
        'cancel_reason',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'order_method' => OrderMethod::class,
            'subtotal' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'has_free_shipping' => 'boolean',
            'shipping_manual' => 'boolean',
            'payment_method' => 'string',
            'payment_status' => PaymentStatus::class,
            'discount_amount' => 'decimal:2',
            'scheduled_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function paymentMethodSlug(): string
    {
        $value = $this->attributes['payment_method'] ?? 'cash';

        return is_string($value) && $value !== '' ? $value : 'cash';
    }

    public function paymentMethodLabel(): string
    {
        $slug = $this->paymentMethodSlug();
        $label = StorePaymentMethod::query()->where('slug', $slug)->value('label');
        if (is_string($label) && $label !== '') {
            return $label;
        }

        return PaymentMethod::tryFrom($slug)?->label() ?? $slug;
    }

    public function canBeCancelledByCustomer(): bool
    {
        return $this->status?->canBeCancelledByCustomer() === true;
    }

    public function isPickup(): bool
    {
        return $this->order_method === OrderMethod::Pickup;
    }

    public function orderMethodLabel(): string
    {
        return $this->order_method?->label() ?? OrderMethod::Delivery->label();
    }

    public function mapsUrl(): ?string
    {
        if ($this->isPickup()) {
            $lat = DeliverySettings::storeLat();
            $lng = DeliverySettings::storeLng();
            if ($lat !== null && $lng !== null) {
                return 'https://www.google.com/maps/search/?api=1&query='.$lat.','.$lng;
            }
        }

        if ($this->relationLoaded('address') && $this->address) {
            return $this->address->mapsUrl();
        }

        $query = collect([
            $this->shipping_details,
            $this->shipping_street,
            $this->shipping_district,
            $this->shipping_city,
        ])->filter(fn ($value) => filled($value))->unique()->implode('، ');

        return $query !== ''
            ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($query)
            : null;
    }

    public function isCashOnDelivery(): bool
    {
        return $this->paymentMethodSlug() === PaymentMethod::Cash->value;
    }

    public function displayNumber(): string
    {
        $raw = trim((string) $this->order_number);
        if (preg_match('/^\d+$/', $raw) === 1) {
            return (string) ((int) $raw);
        }
        if (preg_match('/(\d+)$/', $raw, $matches) === 1) {
            return (string) ((int) $matches[1]);
        }

        return (string) $this->id;
    }
}
