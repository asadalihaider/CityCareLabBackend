<?php

namespace App\Services\Channels\Contracts;

interface OutboxChannelContract
{
    /**
     * @param  string  $mobile  International format, e.g. 923001234567
     * @param  array  $payload  Additional context data (optional)
     * @return bool true = delivered, false = failed / unavailable
     */
    public function send(string $mobile, string $title, string $body, array $payload = []): bool;

    public function isEnabled(): bool;
}
