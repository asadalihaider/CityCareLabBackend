<?php

namespace App\Models;

use App\Models\Enum\BookingStatus;
use App\Models\Enum\BookingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'status',
        'patient_name',
        'contact_number',
        'booking_type',
        'purpose',
        'booking_items',
        'location',
        'booking_date',
    ];

    protected $attributes = [
        'status' => BookingStatus::WAITING,
        'booking_type' => BookingType::TEST,
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'booking_type' => BookingType::class,
        'booking_date' => 'datetime',
        'booking_items' => 'array',
        'location' => 'array',
    ];

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('booking_type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
