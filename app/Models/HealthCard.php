<?php

namespace App\Models;

use App\Models\Enum\PhysicalCardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'max_members',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'features' => 'array',
        'max_members' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isFamilyCard(): bool
    {
        return $this->max_members > 1;
    }

    public function isIndividualCard(): bool
    {
        return $this->max_members === 1;
    }

    public function physicalCards(): HasMany
    {
        return $this->hasMany(PhysicalCard::class);
    }

    public function generatePhysicalCards(int $quantity, int $tenureYears = 2): array
    {
        $expiryDate = now()->addYears($tenureYears);

        return collect(range(1, $quantity))
            ->map(fn () => PhysicalCard::create([
                'health_card_id' => $this->id,
                'serial_number' => $this->generateSerialNumber(),
                'expiry_date' => $expiryDate,
                'status' => PhysicalCardStatus::AVAILABLE,
                'is_active' => true,
            ]))
            ->all();
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
