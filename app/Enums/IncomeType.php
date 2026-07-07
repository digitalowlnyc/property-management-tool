<?php

namespace App\Enums;

enum IncomeType: string
{
    /** Rent — the figure percentage-of-rent expenses are computed against. */
    case Rent = 'rent';

    /** Any other income (parking, laundry, late fees, pet rent, ...). */
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Rent',
            self::Other => 'Other income',
        };
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
