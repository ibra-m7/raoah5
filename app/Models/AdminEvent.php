<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminEvent extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_ORDER_PLACED = 'order_placed';

    public const TYPE_COURIER_ACCEPTED = 'courier_accepted';

    public const TYPE_COURIER_PICKED_UP = 'courier_picked_up';

    public const TYPE_COURIER_DELIVERED = 'courier_delivered';

    public const TYPE_COURIER_SETTLED = 'courier_settled';

    public const TYPE_COURIER_REASSIGNED = 'courier_reassigned';

    protected $fillable = [
        'type',
        'title',
        'body',
        'order_id',
        'courier_id',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function toLiveArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'order_id' => $this->order_id,
            'courier_id' => $this->courier_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_label' => $this->created_at?->format('H:i'),
        ];
    }
}
