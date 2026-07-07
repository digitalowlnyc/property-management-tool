<?php

namespace Database\Seeders;

use App\Enums\AmountType;
use App\Enums\Frequency;
use App\Enums\IncomeType;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $property = Property::factory()->for($user)->create([
            'name' => '123 Main St, Unit 2',
            'address' => '123 Main St, Springfield',
        ]);

        // $2,000/mo rent — the basis for percentage expenses.
        $property->incomes()->create([
            'type' => IncomeType::Rent,
            'label' => 'Rent',
            'amount' => 2000,
            'frequency' => Frequency::Monthly,
        ]);

        // Other monthly income.
        $property->incomes()->create([
            'type' => IncomeType::Other,
            'label' => 'Parking',
            'amount' => 50,
            'frequency' => Frequency::Monthly,
        ]);

        // Fixed $200/mo expense.
        $property->expenses()->create([
            'label' => 'Landscaping',
            'frequency' => Frequency::Monthly,
            'amount_type' => AmountType::Fixed,
            'amount' => 200,
        ]);

        // 8%-of-rent monthly management fee ($160/mo at $2,000 rent).
        $property->expenses()->create([
            'label' => 'Property management',
            'frequency' => Frequency::Monthly,
            'amount_type' => AmountType::Percentage,
            'amount' => 8,
        ]);

        // A yearly cost to show annual vs monthly handling.
        $property->expenses()->create([
            'label' => 'Insurance',
            'frequency' => Frequency::Yearly,
            'amount_type' => AmountType::Fixed,
            'amount' => 1200,
        ]);

        // Leasing fee of one month's rent, charged once on turnover.
        $property->expenses()->create([
            'label' => 'Leasing fee',
            'frequency' => Frequency::OneTime,
            'amount_type' => AmountType::MonthsOfRent,
            'amount' => 1,
        ]);

        // Property tax history for the year-over-year view.
        foreach ([2023 => 3800, 2024 => 4000, 2025 => 4200, 2026 => 4500] as $year => $amount) {
            $property->propertyTaxes()->create(['year' => $year, 'amount' => $amount]);
        }
    }
}
