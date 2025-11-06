<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferCard extends Model
{
    use HasFactory;

    protected $table = 'offer_cards';

    protected $fillable = [
        'title',
        'description',
        'features',
        'link',
        'image',
        'price',
        'serial_prefix',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'features' => 'array',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/'.$this->image);
        }

        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function physicalCards()
    {
        return $this->hasMany(DiscountCard::class, 'offer_card_id');
    }

    public function generateDiscountCards(int $quantity, int $tenureYears = 2): array
    {
        $cards = [];
        $expiryDate = now()->addYears($tenureYears);

        for ($i = 1; $i <= $quantity; $i++) {
            $serialNumber = $this->generateSerialNumber();

            $card = DiscountCard::create([
                'offer_card_id' => $this->id,
                'serial_number' => $serialNumber,
                'expiry_date' => $expiryDate,
                'status' => \App\Models\Enum\DiscountCardStatus::AVAILABLE,
                'is_active' => true,
            ]);

            $cards[] = $card;
        }

        return $cards;
    }

    private function generateSerialNumber(): string
    {
        $prefix = $this->serial_prefix ?: 'CARD';

        do {
            $randomNumber = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $serialNumber = $prefix.$randomNumber;
        } while (DiscountCard::where('serial_number', $serialNumber)->exists());

        return $serialNumber;
    }
}
