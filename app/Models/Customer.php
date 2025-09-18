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
        'location_id',
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

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function location()
    {
        return $this->belongsTo(OperatingCity::class, 'location_id');
    }
}
