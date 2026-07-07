<?php

namespace Database\Factories;

use App\Enums\AmountType;
use App\Enums\Frequency;
use App\Models\Expense;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'label' => fake()->words(2, true),
            'frequency' => Frequency::Monthly,
            'amount_type' => AmountType::Fixed,
            'amount' => fake()->randomFloat(2, 20, 500),
            'rent_basis' => null,
        ];
    }

    /**
     * A percentage-of-rent expense.
     */
    public function percentage(float $percent, ?float $rentBasis = null): static
    {
        return $this->state(fn () => [
            'amount_type' => AmountType::Percentage,
            'amount' => $percent,
            'rent_basis' => $rentBasis,
        ]);
    }
}
