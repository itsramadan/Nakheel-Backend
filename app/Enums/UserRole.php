<?php

namespace App\Enums;

/**
 * Role of an account in the `users` table (staff side of the app).
 * Only "engineer" exists in the current design, kept as an enum so we
 * can add roles (e.g. admin) later without a schema change.
 */
enum UserRole: string
{
    case Engineer = 'engineer';

    public function label(): string
    {
        return match ($this) {
            self::Engineer => 'مهندس زراعي',
        };
    }
}
