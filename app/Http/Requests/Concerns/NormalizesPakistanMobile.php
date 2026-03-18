<?php

namespace App\Http\Requests\Concerns;

use App\Support\PakistanMobile;

trait NormalizesPakistanMobile
{
    protected function normalizePakistanMobileField(string $field): void
    {
        if (! $this->has($field)) {
            return;
        }

        $raw = (string) $this->input($field);
        $normalized = PakistanMobile::normalize($raw);

        $this->merge([
            $field => $normalized ?? preg_replace('/\D/', '', $raw),
        ]);
    }

    protected function normalizePakistanMobileFieldWhenNotEmail(string $field): void
    {
        if (! $this->has($field)) {
            return;
        }

        $raw = (string) $this->input($field);

        if ($raw === '' || filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->normalizePakistanMobileField($field);
    }
}
