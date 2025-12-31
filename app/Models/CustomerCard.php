<?php

namespace App\Models;

use App\Models\Enum\CustomerRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;

class CustomerCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'physical_card_id',
        'is_primary',
        'relationship_type',
        'added_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'relationship_type' => CustomerRelationship::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function physicalCard(): BelongsTo
    {
        return $this->belongsTo(PhysicalCard::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'added_by');
    }

    public function scopeActive($query)
    {
        return $query->whereHas('physicalCard', function ($q) {
            $q->where('status', \App\Models\Enum\PhysicalCardStatus::ACTIVATED)
                ->where('is_active', true)
                ->where('expiry_date', '>', now());
        });
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeDependents($query)
    {
        return $query->where('is_primary', false);
    }

    public function scopeByPhysicalCard($query, int $physicalCardId)
    {
        return $query->where('physical_card_id', $physicalCardId);
    }

    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    public function isDependent(): bool
    {
        return !$this->is_primary;
    }

    public function isFamilyCard(): bool
    {
        return $this->physicalCard->healthCard->isFamilyCard();
    }

    public function isIndividualCard(): bool
    {
        return $this->physicalCard->healthCard->isIndividualCard();
    }

    public function getFamilyMembers(): Collection
    {
        return self::where('physical_card_id', $this->physical_card_id)
            ->with('customer')
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at')
            ->get();
    }

    public function getPrimaryHolder(): ?self
    {
        return self::where('physical_card_id', $this->physical_card_id)
            ->where('is_primary', true)
            ->with('customer')
            ->first();
    }

    public function getDependents(): Collection
    {
        return self::where('physical_card_id', $this->physical_card_id)
            ->where('is_primary', false)
            ->with('customer')
            ->orderBy('created_at')
            ->get();
    }

    public function canAddMember(): bool
    {
        if (!$this->isPrimary()) {
            return false;
        }

        $currentCount = self::where('physical_card_id', $this->physical_card_id)->count();

        return $currentCount < $this->physicalCard->healthCard->max_members;
    }

    /**
     * Activate a new card or add family member to existing card.
     * Validates primary holder existence and member limits for family cards.
     */
    public static function activateCard(
        int $customerId,
        int $physicalCardId,
        bool $isPrimary = true,
        ?CustomerRelationship $relationshipType = null,
        ?int $addedBy = null
    ): self {
        $relationshipType = $relationshipType ?? CustomerRelationship::SELF;

        if (!$isPrimary) {
            $primaryHolder = self::byPhysicalCard($physicalCardId)->primary()->first();

            if (!$primaryHolder) {
                throw new \Exception('Cannot add family member without a primary cardholder.');
            }

            $physicalCard = PhysicalCard::find($physicalCardId);
            $currentCount = self::byPhysicalCard($physicalCardId)->count();

            if ($currentCount >= $physicalCard->healthCard->max_members) {
                throw new \Exception("Maximum {$physicalCard->healthCard->max_members} members allowed for this card.");
            }

            $addedBy = $addedBy ?? $primaryHolder->customer_id;
        }

        if (self::forCustomer($customerId)->byPhysicalCard($physicalCardId)->exists()) {
            throw new \Exception('Customer is already registered with this card.');
        }

        $customerCard = self::create([
            'customer_id' => $customerId,
            'physical_card_id' => $physicalCardId,
            'is_primary' => $isPrimary,
            'relationship_type' => $relationshipType,
            'added_by' => $addedBy,
        ]);

        if ($isPrimary) {
            $customerCard->physicalCard->markAsActivated();
        }

        return $customerCard;
    }

    public function addFamilyMember(int $customerId, CustomerRelationship $relationshipType): self
    {
        if (!$this->isPrimary()) {
            throw new \Exception('Only primary cardholder can add family members.');
        }

        if (!$this->canAddMember()) {
            throw new \Exception('Maximum member limit reached for this card.');
        }

        return self::activateCard(
            customerId: $customerId,
            physicalCardId: $this->physical_card_id,
            isPrimary: false,
            relationshipType: $relationshipType,
            addedBy: $this->customer_id
        );
    }

    public function removeFamilyMember(int $customerCardId): void
    {
        if (!$this->isPrimary()) {
            throw new \Exception('Only primary cardholder can remove family members.');
        }

        $memberCard = self::find($customerCardId);

        if (!$memberCard || $memberCard->physical_card_id !== $this->physical_card_id) {
            throw new \Exception('Family member not found on this card.');
        }

        if ($memberCard->isPrimary()) {
            throw new \Exception('Cannot remove the primary cardholder.');
        }

        $memberCard->delete();
    }

    public function removeCard(): void
    {
        if ($this->isPrimary() && $this->getFamilyMembers()->count() > 1) {
            throw new \Exception('Cannot remove primary holder while dependents exist. Remove dependents first.');
        }

        if ($this->getFamilyMembers()->count() === 1) {
            $this->physicalCard->markAsAvailable();
        }

        $this->delete();
    }

    public function getCardDetailsAttribute(): array
    {
        $familyMembers = $this->getFamilyMembers();

        return [
            'id' => $this->physicalCard->id,
            'identifier' => $this->physicalCard->serial_number,
            'expiry_date' => $this->physicalCard->expiry_date->format('Y-m-d'),
            'status' => $this->physicalCard->status->value,
            'is_active' => $this->physicalCard->is_active,
            'is_family_card' => $this->isFamilyCard(),
            'is_primary_holder' => $this->isPrimary(),
            'relationship' => $this->relationship_type->label(),
            'activated_at' => $this->created_at->toISOString(),
            'family_members_count' => $familyMembers->count(),
            'max_members' => $this->physicalCard->healthCard->max_members,
            'can_add_members' => $this->canAddMember(),
            'family_members' => $familyMembers->map(fn ($member) => [
                'id' => $member->id,
                'customer_id' => $member->customer_id,
                'customer_name' => $member->customer->name,
                'is_primary' => $member->is_primary,
                'relationship' => $member->relationship_type->label(),
                'added_at' => $member->created_at->format('Y-m-d H:i:s'),
            ])->toArray(),
        ];
    }
}
