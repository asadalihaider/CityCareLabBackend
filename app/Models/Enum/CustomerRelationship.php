<?php

namespace App\Models\Enum;

enum CustomerRelationship: string
{
    case SELF = 'self';
    case SPOUSE = 'spouse';
    case CHILD = 'child';
    case PARENT = 'parent';
    case SIBLING = 'sibling';
    case OTHER = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::SELF => 'Primary Holder',
            self::SPOUSE => 'Spouse',
            self::CHILD => 'Child',
            self::PARENT => 'Parent',
            self::SIBLING => 'Sibling',
            self::OTHER => 'Other Family Member',
        };
    }

    public function isPrimary(): bool
    {
        return $this === self::SELF;
    }

    public function isDependent(): bool
    {
        return ! $this->isPrimary();
    }
}
