<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationCampaign extends Model
{
    protected $fillable = [
        'title',
        'body',
        'type',
        'audience',
        'recipients_count',
        'push_count',
        'created_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'recipients_count' => 'integer',
            'push_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class, 'campaign_id');
    }
}
