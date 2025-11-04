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
        'link',
        'image',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
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

    // TODO: Add relationship to physical discount cards after creating DiscountCard model
    // public function physicalCards()
    // {
    //     return $this->hasMany(DiscountCard::class, 'offer_card_id');
    // }
}
