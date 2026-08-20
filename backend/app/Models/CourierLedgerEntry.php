<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierLedgerEntry extends Model
{
    public const TYPE_COD = 'cod_collected';

    public const TYPE_SETTLEMENT = 'settlement';

    public const DIRECTION_DEBIT = 'debit';

    public const DIRECTION_CREDIT = 'credit';

    protected $fillable = [
        'courier_id',
        'order_id',
        'type',
        'direction',
        'amount',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_COD => 'تحصيل طلب (الدفع عند الاستلام)',
            self::TYPE_SETTLEMENT => 'تسديد للمخزن',
            default => $this->type,
        };
    }
}
