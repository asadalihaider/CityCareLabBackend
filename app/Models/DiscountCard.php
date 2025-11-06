<?php

namespace App\Models;

use App\Models\Enum\DiscountCardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiscountCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_card_id',
        'serial_number',
        'expiry_date',
        'status',
        'is_active',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'status' => DiscountCardStatus::class,
        'is_active' => 'boolean',
    ];

    // Relationships
    public function offerCard(): BelongsTo
    {
        return $this->belongsTo(OfferCard::class);
    }

    public function customerCard(): HasOne
    {
        return $this->hasOne(CustomerCard::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', DiscountCardStatus::AVAILABLE)
            ->where('is_active', true)
            ->where('expiry_date', '>', now());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNotIn('status', [DiscountCardStatus::DEACTIVATED, DiscountCardStatus::EXPIRED])
            ->where('expiry_date', '>', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expiry_date', '>', now());
    }

    public function scopeBySerialAndExpiry($query, $serial, $expiry)
    {
        return $query->where('serial_number', $serial)
            ->where('expiry_date', $expiry);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date <= now();
    }

    public function markAsActivated(): void
    {
        $this->update(['status' => DiscountCardStatus::ATTACHED]);
    }

    public function markAsAvailable(): void
    {
        $this->update(['status' => DiscountCardStatus::AVAILABLE]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }
}
