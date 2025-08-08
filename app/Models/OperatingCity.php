<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperatingCity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'province',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvince($query, string $province)
    {
        return $query->where('province', $province);
    }

    public function labCenters(): HasMany
    {
        return $this->hasMany(LabCenter::class);
    }
}
