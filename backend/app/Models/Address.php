<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'city',
        'district',
        'street',
        'details',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function displayLine(): string
    {
        return collect([$this->details, $this->street, $this->district, $this->city])
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->implode('، ');
    }

    public function mapsUrl(): ?string
    {
        if ($this->latitude !== null && $this->longitude !== null) {
            return 'https://www.google.com/maps/search/?api=1&query='
                .urlencode(((float) $this->latitude).','.((float) $this->longitude));
        }

        $query = $this->displayLine();

        return $query !== ''
            ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($query)
            : null;
    }
}
