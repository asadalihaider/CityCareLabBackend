<?php

namespace App\Models;

use App\Models\Enum\TestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'short_title',
        'duration',
        'specimen',
        'type',
        'includes',
        'prerequisites',
        'price',
        'discount',
        'relevant_diseases',
        'relevant_symptoms',
        'is_active',
        'is_featured',
        'image',
    ];

    protected $casts = [
        'type' => TestType::class,
        'includes' => 'array',
        'prerequisites' => 'array',
        'relevant_diseases' => 'array',
        'relevant_symptoms' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(TestCategory::class, 'category_test');
    }
}
