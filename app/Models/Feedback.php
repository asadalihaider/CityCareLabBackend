<?php

namespace App\Models;

use App\Models\Enum\FeedbackCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'subject',
        'message',
        'rating',
        'category',
        'is_anonymous',
        'contact_email',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'metadata' => 'array',
            'rating' => 'integer',
            'category' => FeedbackCategory::class,
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
