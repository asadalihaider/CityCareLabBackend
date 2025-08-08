<?php

namespace App\Models;

use App\Models\Enum\TestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'short_title',
        'duration',
        'type',
        'includes',
        'price',
        'sale_price',
        'is_active',
    ];

    protected $casts = [
        'type' => TestType::class,
        'includes' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function categories()
    {
        return $this->belongsToMany(TestCategory::class, 'category_test');
    }
}
