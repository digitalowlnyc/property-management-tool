<?php

namespace App\Enums;

enum Frequency: string
{
    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    /**
     * Human-friendly label for the frequency.
     */
    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'One-time',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
        };
    }

    /**
     * Value/label pairs for populating a frontend select.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * Amortized monthly figure: the annualized cost spread across 12 months, so
     * a one-time amount shows its amortized share rather than zero. Equivalent
     * to annualAmount($amount) / 12 for every frequency. Used for the per-item
     * "monthly equivalent" display.
     */
    public function monthlyEquivalent(float $amount): float
    {
        return match ($this) {
            self::OneTime => $amount / 12,
            self::Monthly => $amount,
            self::Quarterly => $amount / 3,
            self::Yearly => $amount / 12,
        };
    }

    /**
     * Recurring monthly run-rate. A one-time amount contributes nothing because
     * it is not an ongoing monthly cost; it only shows up in the annual total.
     * Used for the Cashflow "this month" figures.
     */
    public function monthlyRunRate(float $amount): float
    {
        return match ($this) {
            self::OneTime => 0.0,
            self::Monthly => $amount,
            self::Quarterly => $amount / 3,
            self::Yearly => $amount / 12,
        };
    }

    /**
     * Normalize a per-occurrence amount to its annual total.
     *
     * One-time amounts are counted exactly once within the year.
     */
    public function annualAmount(float $amount): float
    {
        return match ($this) {
            self::OneTime => $amount,
            self::Monthly => $amount * 12,
            self::Quarterly => $amount * 4,
            self::Yearly => $amount,
        };
    }
}
