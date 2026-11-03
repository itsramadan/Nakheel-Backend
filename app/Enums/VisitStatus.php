<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'مجدولة',
            self::Completed => 'تمت',
            self::Cancelled => 'ملغاة',
        };
    }
}
