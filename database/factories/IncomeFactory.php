<?php

namespace Database\Factories;

use App\Enums\Frequency;
use App\Enums\IncomeType;
use App\Models\Income;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'type' => IncomeType::Other,
            'label' => fake()->word(),
            'amount' => fake()->randomFloat(2, 50, 500),
            'frequency' => Frequency::Monthly,
            'start_date' => null,
            'end_date' => null,
        ];
    }

    /**
     * A rent income entry, active by default (open-ended dates).
     */
    public function rent(float $amount = 2000): static
    {
        return $this->state(fn () => [
            'type' => IncomeType::Rent,
            'label' => 'Rent',
            'amount' => $amount,
            'frequency' => Frequency::Monthly,
        ]);
    }
}
