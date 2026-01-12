<?php

namespace App\Models;

use App\Models\Enum\CustomerRelationship;
use Filament\Notifications\Notification;
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

    public static function activateCard(
        int $customerId,
        int $physicalCardId,
        bool $isPrimary = true,
        ?CustomerRelationship $relationshipType = null,
        ?int $addedBy = null
    ): ?self {
        $relationshipType = $relationshipType ?? CustomerRelationship::SELF;

        if (!$isPrimary) {
            $primaryHolder = self::byPhysicalCard($physicalCardId)->primary()->first();

            if (!$primaryHolder) {
                Notification::make()
                    ->title('Cannot add family member')
                    ->body('This card does not have a primary cardholder yet.')
                    ->danger()
                    ->send();
                return null;
            }

            $physicalCard = PhysicalCard::find($physicalCardId);
            $currentCount = self::byPhysicalCard($physicalCardId)->count();

            if ($currentCount >= $physicalCard->healthCard->max_members) {
                Notification::make()
                    ->title('Member limit reached')
                    ->body("Maximum {$physicalCard->healthCard->max_members} members allowed for this card.")
                    ->warning()
                    ->send();
                return null;
            }

            $addedBy = $addedBy ?? $primaryHolder->customer_id;
        }

        if (self::forCustomer($customerId)->byPhysicalCard($physicalCardId)->exists()) {
            Notification::make()
                ->title('Already registered')
                ->body('This customer is already registered with this card.')
                ->warning()
                ->send();
            return null;
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

        Notification::make()
            ->title('Card activated successfully')
            ->success()
            ->send();

        return $customerCard;
    }

    public function addFamilyMember(int $customerId, CustomerRelationship $relationshipType): ?self
    {
        if (!$this->isPrimary()) {
            Notification::make()
                ->title('Permission denied')
                ->body('Only primary cardholder can add family members.')
                ->danger()
                ->send();
            return null;
        }

        if (!$this->canAddMember()) {
            Notification::make()
                ->title('Member limit reached')
                ->body('Maximum member limit reached for this card.')
                ->warning()
                ->send();
            return null;
        }

        return self::activateCard(
            customerId: $customerId,
            physicalCardId: $this->physical_card_id,
            isPrimary: false,
            relationshipType: $relationshipType,
            addedBy: $this->customer_id
        );
    }

    public function removeFamilyMember(int $customerCardId): bool
    {
        if (!$this->isPrimary()) {
            Notification::make()
                ->title('Permission denied')
                ->body('Only primary cardholder can remove family members.')
                ->danger()
                ->send();
            return false;
        }

        $memberCard = self::find($customerCardId);

        if (!$memberCard || $memberCard->physical_card_id !== $this->physical_card_id) {
            Notification::make()
                ->title('Member not found')
                ->body('Family member not found on this card.')
                ->warning()
                ->send();
            return false;
        }

        if ($memberCard->isPrimary()) {
            Notification::make()
                ->title('Cannot remove primary')
                ->body('Cannot remove the primary cardholder.')
                ->danger()
                ->send();
            return false;
        }

        $memberCard->delete();

        Notification::make()
            ->title('Member removed')
            ->body('Family member has been removed from the card.')
            ->success()
            ->send();

        return true;
    }

    public function removeCard(): bool
    {
        if ($this->isPrimary() && $this->getFamilyMembers()->count() > 1) {
            Notification::make()
                ->title('Cannot remove primary holder')
                ->body('Remove all dependents first before removing the primary holder.')
                ->danger()
                ->send();
            return false;
        }

        if ($this->getFamilyMembers()->count() === 1) {
            $this->physicalCard->markAsAvailable();
        }

        $this->delete();

        Notification::make()
            ->title('Card removed')
            ->body('Card has been removed from customer.')
            ->success()
            ->send();

        return true;
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
