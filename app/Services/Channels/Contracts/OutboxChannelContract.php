<?php

namespace App\Services\Channels\Contracts;

use App\Services\Channels\Data\ChannelSendResult;

interface OutboxChannelContract
{
    /**
     * @param  string  $mobile  International format, e.g. 923001234567
     * @param  array  $payload  Additional context data (optional)
     */
    public function send(string $mobile, array $payload = []): ChannelSendResult;

    public function isEnabled(): bool;
}
