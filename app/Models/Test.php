<?php

namespace App\Models;

use App\Models\Enum\TestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'duration',
        'type',
        'categories',
        'includes',
        'price',
        'sale_price',
        'is_active',
    ];

    protected $casts = [
        'type' => TestType::class,
        'categories' => 'array',
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

    public function getTestCategories()
    {
        if (empty($this->categories)) {
            return collect();
        }

        return TestCategory::whereIn('id', $this->categories)->get();
    }
}
