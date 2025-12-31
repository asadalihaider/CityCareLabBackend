<?php

namespace App\Models;

use App\Models\Enum\CustomerStatus;
use App\Models\Enum\Gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'mobile_number',
        'email',
        'password',
        'status',
        'city_id',
        'dob',
        'image',
        'gender',
        'mobile_verified_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'mobile_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
            'status' => CustomerStatus::class,
            'gender' => Gender::class,
        ];
    }

    public function getAuthIdentifierName()
    {
        return 'mobile_number';
    }

    public function isMobileVerified(): bool
    {
        return ! is_null($this->mobile_verified_at);
    }

    public function markMobileAsVerified(): bool
    {
        return $this->forceFill([
            'mobile_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function isEmailVerified(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function getFamilyCards()
    {
        return $this->customerCards()
            ->whereHas('physicalCard.healthCard', fn ($query) => $query->where('max_members', '>', 1))
            ->with(['physicalCard.healthCard', 'customer'])
            ->get();
    }

    public function getIndividualCards()
    {
        return $this->customerCards()
            ->whereHas('physicalCard.healthCard', fn ($query) => $query->where('max_members', 1))
            ->with('physicalCard.healthCard')
            ->get();
    }

    public function expoTokens()
    {
        return $this->morphMany(ExpoToken::class, 'owner');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function city()
    {
        return $this->belongsTo(OperatingCity::class, 'city_id');
    }

    public function customerCards()
    {
        return $this->hasMany(CustomerCard::class);
    }

    public function activeCard()
    {
        return $this->hasOne(CustomerCard::class)->active();
    }

    public function primaryCards()
    {
        return $this->hasMany(CustomerCard::class)->where('is_primary', true);
    }

    public function familyMemberships()
    {
        return $this->hasMany(CustomerCard::class)->where('is_primary', false);
    }

    public function addedFamilyMembers()
    {
        return $this->hasMany(CustomerCard::class, 'added_by');
    }
}
