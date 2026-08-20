<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Courier extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'password',
        'is_active',
        'is_online',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_online' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function deliveredOrders(): HasMany
    {
        return $this->hasMany(Order::class)->where('status', OrderStatus::Delivered);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CourierLedgerEntry::class)->latest();
    }

    public function phoneDisplay(): string
    {
        return Phone::display((string) $this->phone);
    }

    public function canReceiveOrders(): bool
    {
        return $this->is_active && $this->is_online;
    }
}
