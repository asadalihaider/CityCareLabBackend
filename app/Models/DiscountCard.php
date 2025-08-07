<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'link',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
