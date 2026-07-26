<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Pending = 'pending';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Published => 'Published',
        };
    }
}
