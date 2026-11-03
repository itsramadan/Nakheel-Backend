<?php

namespace App\Enums;

/**
 * برنامج التاسك (4 برامج): الري / التسميد / الرش / العمليات الزراعية.
 */
enum TaskCategory: string
{
    case Irrigation = 'irrigation';
    case Fertilizing = 'fertilizing';
    case Spraying = 'spraying';
    case AgriculturalOperations = 'agricultural_operations';

    public function label(): string
    {
        return match ($this) {
            self::Irrigation => 'برنامج الري',
            self::Fertilizing => 'برنامج التسميد',
            self::Spraying => 'برنامج الرش',
            self::AgriculturalOperations => 'برنامج العمليات الزراعية',
        };
    }
}
