<?php

namespace App\Enums;

enum AmountType: string
{
    /** A fixed dollar amount. */
    case Fixed = 'fixed';

    /** A percentage of the property's rent. */
    case Percentage = 'percentage';

    /** A number of months (1–12) of the property's rent. */
    case MonthsOfRent = 'months_of_rent';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed amount',
            self::Percentage => 'Percentage of rent',
            self::MonthsOfRent => 'Months of rent',
        };
    }

    /** Whether this amount type is computed against the property's rent. */
    public function usesRentBasis(): bool
    {
        return $this === self::Percentage || $this === self::MonthsOfRent;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
