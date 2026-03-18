<?php

namespace App\Support;

final class PakistanMobile
{
    /**
     * Canonical format: 92XXXXXXXXXX (12 digits)
     */
    public static function normalize(?string $input): ?string
    {
        if (! is_string($input) || trim($input) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $input);

        if (preg_match('/^92(3\d{9})$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^03\d{9}$/', $digits)) {
            return '92'.substr($digits, 1);
        }

        if (preg_match('/^0092(3\d{9})$/', $digits, $matches)) {
            return '92'.$matches[1];
        }

        if (preg_match('/^3\d{9}$/', $digits)) {
            return '92'.$digits;
        }

        return null;
    }

    /**
     * Local display format: 03XXXXXXXXX
     */
    public static function toLocal(string $canonical): string
    {
        return preg_match('/^92(3\d{9})$/', $canonical, $matches)
            ? '0'.$matches[1]
            : $canonical;
    }
}
