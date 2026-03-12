<?php

namespace App\Models;

use App\Models\Enum\OutboxChannel;
use App\Models\Enum\OutboxStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OutboxLog extends Model
{
    protected $table = 'outbox_logs';

    protected $fillable = [
        'mobile',
        'event',
        'channel',
        'title',
        'body',
        'status',
        'response',
        'payload',
        'scheduled_at',
        'processed_at',
    ];

    protected $casts = [
        'channel' => OutboxChannel::class,
        'status' => OutboxStatus::class,
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function scopeByChannel(Builder $query, OutboxChannel $channel): Builder
    {
        return $query->where('channel', $channel->value);
    }

    public function scopeByStatus(Builder $query, OutboxStatus $status): Builder
    {
        return $query->where('status', $status->value);
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
