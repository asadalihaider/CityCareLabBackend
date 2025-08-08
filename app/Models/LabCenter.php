<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'phone',
        'secondary_phone',
        'rating',
        'operating_city_id',
        'is_active',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('operating_city_id', $cityId);
    }

    public function operatingCity(): BelongsTo
    {
        return $this->belongsTo(OperatingCity::class);
    }
}
