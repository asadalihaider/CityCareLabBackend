<?php

namespace App\Services\Channels\Data;

final class ChannelSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $reason = null,
    ) {}

    public static function ok(?string $reason = null): self
    {
        return new self(true, $reason);
    }

    public static function fail(?string $reason = null): self
    {
        return new self(false, $reason);
    }
}
