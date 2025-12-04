<?php

namespace App\Models;

use YieldStudio\LaravelExpoNotifier\Models\ExpoToken as BaseExpoToken;

class ExpoToken extends BaseExpoToken
{
    protected $casts = [
        'last_used' => 'datetime',
    ];

    public static function createOrUpdate(string $tokenValue, ?int $customerId = null): self
    {
        $tokenModel = self::firstOrNew(['value' => $tokenValue]);

        if ($customerId) {
            $tokenModel->owner_type = Customer::class;
            $tokenModel->owner_id = $customerId;
        } else {
            $tokenModel->owner_type = null;
            $tokenModel->owner_id = null;
        }

        $tokenModel->save();

        return $tokenModel;
    }

    public function scopeAnonymous($query)
    {
        return $query->whereNull('owner_type')->whereNull('owner_id');
    }
}
