<?php

namespace App\Enums;

enum Plan: string
{
    case Free = 'free';
    case Basic = 'basic';
    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Basic => 'Basic',
            self::Pro => 'Pro',
        };
    }

    public function quota(): int
    {
        return match ($this) {
            self::Free => 10,
            self::Basic => 200,
            self::Pro => 1000,
        };
    }
}
