<?php

namespace App\Services\Channels\Concerns;

trait ResolvesMessagePayload
{
    protected function resolveMessagePart(?string $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
