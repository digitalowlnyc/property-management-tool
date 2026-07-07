<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyTax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyTax>
 */
class PropertyTaxFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'year' => (int) now()->year,
            'amount' => fake()->randomFloat(2, 1000, 8000),
            'estimated' => false,
        ];
    }
}
