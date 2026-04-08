<?php

namespace App\Models;

use App\Models\Enum\OutboxStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OutboxLog extends Model
{
    protected $table = 'outbox_logs';

    protected $fillable = [
        'mobile',
        'event',
        'title',
        'body',
        'response',
        'payload',
        'attempts',
        'scheduled_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'array',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    protected $appends = ['status'];

    public function getStatusAttribute(): OutboxStatus
    {
        $attempts = $this->attempts ?? [];

        if (! is_array($attempts) || empty($attempts)) {
            return OutboxStatus::FAILED;
        }

        foreach ($attempts as $attempt) {
            if (isset($attempt['status']) && $attempt['status'] === 'sent') {
                return OutboxStatus::SENT;
            }
        }

        return OutboxStatus::FAILED;
    }

    public function scopeForMobile(Builder $query, string $mobile): Builder
    {
        return $query->where('mobile', $mobile);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }
}
