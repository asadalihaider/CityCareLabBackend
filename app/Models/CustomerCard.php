<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'discount_card_id',
    ];

    protected $casts = [
        //
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discountCard(): BelongsTo
    {
        return $this->belongsTo(DiscountCard::class);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('discountCard', function ($q) {
            $q->where('status', \App\Models\Enum\DiscountCardStatus::ATTACHED)
                ->where('is_active', true)
                ->where('expiry_date', '>', now());
        });
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function removeCard(): void
    {
        $this->discountCard->markAsAvailable();

        $this->delete();
    }

    public static function attachCard($customerId, $discountCardId): self
    {
        $customerCard = self::create([
            'customer_id' => $customerId,
            'discount_card_id' => $discountCardId,
        ]);

        $customerCard->discountCard->markAsActivated();

        return $customerCard;
    }

    public function getCardDetailsAttribute(): array
    {
        return [
            'id' => $this->discountCard->id,
            'identifier' => $this->discountCard->serial_number,
            'expiry_date' => $this->discountCard->expiry_date->format('Y-m-d'),
            'status' => $this->discountCard->status->value,
            'attached_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
