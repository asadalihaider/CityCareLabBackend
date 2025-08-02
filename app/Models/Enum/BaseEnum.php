<?php

namespace App\Models\Enum;

use Illuminate\Support\Collection;

trait BaseEnum
{
    public function is(self $enum): bool
    {
        return $this === $enum;
    }

    public function label(): string
    {
        return __('enum.'.class_basename($this).'.'.$this->name);
    }

    public static function getCase(int|string $key): mixed
    {
        return self::tryFrom($key);
    }

    public static function collect(): Collection
    {
        return collect(static::cases());
    }

    public static function values(): array
    {
        return collect(self::cases())->pluck('value')->toArray();
    }

    public static function toOptions(): array
    {
        return self::collect()->mapWithKeys(fn (self $enum) => [$enum->value => $enum->label()])->all();
    }
}
