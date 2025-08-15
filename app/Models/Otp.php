<?php

namespace App\Models;

use App\Models\Enum\OtpType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'otp',
        'type',
        'expires_at',
        'verified_at',
        'attempts'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'type' => OtpType::class,
    ];

    const MAX_ATTEMPTS = 3;
    const EXPIRY_MINUTES = 5;

    public function scopeValid($query)
    {
        return $query->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', self::MAX_ATTEMPTS);
    }

    public function scopeForIdentifier($query, $identifier)
    {
        return $query->where('identifier', $identifier);
    }

    public function scopeOfType($query, OtpType $type)
    {
        return $query->where('type', $type);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public static function generateOtp(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function createForIdentifier(string $identifier, OtpType $type): self
    {
        self::forIdentifier($identifier)->ofType($type)->delete();

        return self::create([
            'identifier' => $identifier,
            'otp' => self::generateOtp(),
            'type' => $type,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'attempts' => 0,
        ]);
    }

    public function getDeliveryMethod(): string
    {
        if (filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        if (preg_match('/^((\+92)?(0092)?(92)?(0)?)(3)([0-9]{9})$/', $this->identifier)) {
            return 'sms';
        }

        return 'unknown';
    }

    public function shouldDeliverViaSms(): bool
    {
        return $this->getDeliveryMethod() === 'sms';
    }

    public function shouldDeliverViaEmail(): bool
    {
        return $this->getDeliveryMethod() === 'email';
    }
}
