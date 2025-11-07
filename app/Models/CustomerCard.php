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
        'physical_card_id',
    ];

    protected $casts = [
        //
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function physicalCard(): BelongsTo
    {
        return $this->belongsTo(PhysicalCard::class);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('physicalCard', function ($q) {
            $q->where('status', \App\Models\Enum\PhysicalCardStatus::ACTIVATED)
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
        $this->physicalCard->markAsAvailable();

        $this->delete();
    }

    public static function activateCard($customerId, $physicalCardId): self
    {
        $customerCard = self::create([
            'customer_id' => $customerId,
            'physical_card_id' => $physicalCardId,
        ]);

        $customerCard->physicalCard->markAsActivated();

        return $customerCard;
    }

    public function getCardDetailsAttribute(): array
    {
        return [
            'id' => $this->physicalCard->id,
            'identifier' => $this->physicalCard->serial_number,
            'expiry_date' => $this->physicalCard->expiry_date->format('Y-m-d'),
            'status' => $this->physicalCard->status->value,
            'is_active' => $this->physicalCard->is_active,
            'activated_at' => $this->created_at->toISOString(),
            'deactivated_at' => $this->updated_at->toISOString(),
        ];
    }
}
