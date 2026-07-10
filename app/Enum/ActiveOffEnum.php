<?php

namespace App\Enum;

enum ActiveOffEnum: string
{
    case ACTIVE = 'active';
    case OFF = 'off';

    public function formatToString(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::OFF => 'Off',
        };
    }

    public static function formatToEnum(string $value): ActiveOffEnum
    {
        return match ($value) {
            'active' => self::ACTIVE,
            'off' => self::OFF,
            default => throw new \InvalidArgumentException("Invalid value for ActiveOffEnum"),
        };
    }
}
