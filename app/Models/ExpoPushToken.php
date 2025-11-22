<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpoPushToken extends Model
{
    protected $fillable = [
        'token',
        'customer_id',
        'last_used',
    ];

    protected $casts = [
        'last_used' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
