<?php

namespace App\Models;

use App\Models\Enum\PhysicalCardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PhysicalCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'health_card_id',
        'serial_number',
        'expiry_date',
        'status',
        'is_active',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'status' => PhysicalCardStatus::class,
        'is_active' => 'boolean',
    ];

    // Relationships
    public function healthCard(): BelongsTo
    {
        return $this->belongsTo(HealthCard::class);
    }

    public function customerCard(): HasOne
    {
        return $this->hasOne(CustomerCard::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', PhysicalCardStatus::AVAILABLE)
            ->where('is_active', true)
            ->where('expiry_date', '>', now());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNotIn('status', [PhysicalCardStatus::DEACTIVATED, PhysicalCardStatus::EXPIRED])
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
        $this->update(['status' => PhysicalCardStatus::ACTIVATED]);
    }

    public function markAsAvailable(): void
    {
        $this->update(['status' => PhysicalCardStatus::AVAILABLE]);
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
