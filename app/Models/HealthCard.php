<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCard extends Model
{
    use HasFactory;

    protected $table = 'health_cards';

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function physicalCards()
    {
        return $this->hasMany(PhysicalCard::class, 'health_card_id');
    }

    public function generatePhysicalCards(int $quantity, int $tenureYears = 2): array
    {
        $cards = [];
        $expiryDate = now()->addYears($tenureYears);

        for ($i = 1; $i <= $quantity; $i++) {
            $serialNumber = $this->generateSerialNumber();

            $card = PhysicalCard::create([
                'health_card_id' => $this->id,
                'serial_number' => $serialNumber,
                'expiry_date' => $expiryDate,
                'status' => \App\Models\Enum\PhysicalCardStatus::AVAILABLE,
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
        } while (PhysicalCard::where('serial_number', $serialNumber)->exists());

        return $serialNumber;
    }
}
