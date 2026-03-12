<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    protected $fillable = [
        'name',
        'api_key',
        'is_active',
        'rate_limit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate_limit' => 'integer',
    ];

    protected $hidden = ['api_key'];

    public static function generateApiKey(): string
    {
        return Str::random(64);
    }

    public static function findByKey(string $key): ?self
    {
        return self::where('api_key', $key)
            ->where('is_active', true)
            ->first();
    }

    public function maskedKey(): string
    {
        return substr($this->api_key, 0, 6).'...'.substr($this->api_key, -4);
    }
}
