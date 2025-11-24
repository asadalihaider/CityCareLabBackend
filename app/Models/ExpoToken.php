<?php

namespace App\Models;

use YieldStudio\LaravelExpoNotifier\Models\ExpoToken as BaseExpoToken;

class ExpoToken extends BaseExpoToken
{
    protected $fillable = [
        'last_used',
    ];

    protected $casts = [
        'last_used' => 'datetime',
    ];

    public static function createOrUpdate(string $tokenValue, ?int $customerId = null): self
    {
        $tokenModel = self::firstOrNew(['value' => $tokenValue]);

        if ($customerId) {
            $tokenModel->owner_type = Customer::class;
            $tokenModel->owner_id   = $customerId;
        } else {
            $tokenModel->owner_type = null;
            $tokenModel->owner_id   = null;
        }

        $tokenModel->save();

        return $tokenModel;
    }

    public function makeAnonymous(): self
    {
        $this->update([
            'owner_type' => null,
            'owner_id' => null,
        ]);

        return $this;
    }

    public function isAnonymous(): bool
    {
        return is_null($this->owner_id) && is_null($this->owner_type);
    }

    public function scopeAnonymous($query)
    {
        return $query->whereNull('owner_type')->whereNull('owner_id');
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('owner_type', Customer::class)
                    ->where('owner_id', $customerId);
    }
}