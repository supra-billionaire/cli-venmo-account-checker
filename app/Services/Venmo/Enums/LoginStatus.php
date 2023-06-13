<?php

namespace App\Services\Venmo\Enums;

enum LoginStatus: string
{
    case VALID = 'VALID';
    case INVALID = 'INVALID';
    case ERROR = 'ERROR';

    public function getTextColor(): string
    {
        return match ($this) {
            self::VALID => 'text-green-500',
            self::INVALID => 'text-red-500',
            self::ERROR => 'text-purple-500',
        };
    }

    public function getStatusText(): string
    {
        return match ($this) {
            self::VALID => 'LIVE',
            self::INVALID => 'DIE',
            self::ERROR => 'ERROR',
        };
    }
}
