<?php

namespace Tests\Feature;

use App\Enums\AmountType;
use App\Enums\Frequency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Property;
use App\Models\PropertyTax;
use App\Services\CashflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CashflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private function summarize(Property $property): array
    {
        return app(CashflowService::class)
            ->forProperty($property->fresh(['incomes', 'expenses', 'propertyTaxes']));
    }

    public function test_estimate_income_ignores_ended_and_future_leases(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 7));

        $property = Property::factory()->create();
        // Prior tenant whose lease already ended.
        Income::factory()->for($property)->rent(1950)->create([
            'start_date' => '2025-05-15',
            'end_date' => '2026-05-14',
        ]);
        // Current tenant.
        Income::factory()->for($property)->rent(1850)->create([
            'start_date' => '2026-06-01',
            'end_date' => null,
        ]);

        $summary = app(CashflowService::class)
            ->forProperty($property->fresh(['incomes', 'expenses', 'propertyTaxes']));

        // Only the active $1,850 lease counts — the two are not summed.
        $this->assertSame(1850.0, $summary['monthly_income']);
        $this->assertSame(22200.0, $summary['annual_income']);
    }

    public function test_it_nets_rent_against_fixed_and_percentage_expenses(): void
    {
        $property = Property::factory()->create();
        Income::factory()->for($property)->rent(2000)->create();
        Expense::factory()->for($property)->create([
            'label' => 'Landscaping',
            'frequency' => Frequency::Monthly,
            'amount_type' => AmountType::Fixed,
            'amount' => 200,
        ]);
        Expense::factory()->for($property)->percentage(8)->create([
            'label' => 'Management',
            'frequency' => Frequency::Monthly,
        ]);

        $summary = $this->summarize($property);

        // 8% of $2,000 = $160.
        $this->assertSame(2000.0, $summary['monthly_income']);
        $this->assertSame(360.0, $summary['monthly_expenses']);
        $this->assertSame(1640.0, $summary['monthly_net']);
        $this->assertSame(24000.0, $summary['annual_income']);
        $this->assertSame(4320.0, $summary['annual_expenses']);
        $this->assertSame(19680.0, $summary['annual_net']);
    }

    public function test_one_time_expense_hits_the_year_but_not_the_monthly_run_rate(): void
    {
        $property = Property::factory()->create();
        Income::factory()->for($property)->rent(2000)->create();
        Expense::factory()->for($property)->create([
            'frequency' => Frequency::OneTime,
            'amount_type' => AmountType::Fixed,
            'amount' => 600,
        ]);

        $summary = $this->summarize($property);

        // One-time items are excluded from the recurring monthly figure.
        $this->assertSame(0.0, $summary['monthly_expenses']);
        $this->assertSame(2000.0, $summary['monthly_net']);
        $this->assertSame(600.0, $summary['annual_expenses']);
        $this->assertSame(23400.0, $summary['annual_net']);
    }

    public function test_percentage_expense_uses_explicit_rent_basis_over_current_rent(): void
    {
        $property = Property::factory()->create();
        Income::factory()->for($property)->rent(2000)->create();
        // 10% but pinned to a $1,000 basis => $100/mo regardless of the $2,000 rent.
        Expense::factory()->for($property)->percentage(10, rentBasis: 1000)->create([
            'frequency' => Frequency::Monthly,
        ]);

        $summary = $this->summarize($property);

        $this->assertSame(100.0, $summary['monthly_expenses']);
    }

    public function test_quarterly_and_yearly_frequencies_normalize_correctly(): void
    {
        $property = Property::factory()->create();
        // $300/quarter => $100/mo, $1,200/yr.
        Income::factory()->for($property)->create([
            'frequency' => Frequency::Quarterly,
            'amount' => 300,
        ]);
        // $1,200/yr => $100/mo, $1,200/yr.
        Expense::factory()->for($property)->create([
            'frequency' => Frequency::Yearly,
            'amount_type' => AmountType::Fixed,
            'amount' => 1200,
        ]);

        $summary = $this->summarize($property);

        $this->assertSame(100.0, $summary['monthly_income']);
        $this->assertSame(1200.0, $summary['annual_income']);
        $this->assertSame(100.0, $summary['monthly_expenses']);
        $this->assertSame(1200.0, $summary['annual_expenses']);
    }

    public function test_months_of_rent_expense_multiplies_the_monthly_rent(): void
    {
        $property = Property::factory()->create();
        Income::factory()->for($property)->rent(2000)->create();
        // A one-time leasing fee of 1 month's rent = $2,000 in the year, $0 run-rate.
        Expense::factory()->for($property)->create([
            'frequency' => Frequency::OneTime,
            'amount_type' => AmountType::MonthsOfRent,
            'amount' => 1,
        ]);

        $summary = $this->summarize($property);

        $this->assertSame(0.0, $summary['monthly_expenses']);
        $this->assertSame(2000.0, $summary['annual_expenses']);
    }

    public function test_current_year_property_tax_counts_as_a_yearly_expense(): void
    {
        $property = Property::factory()->create();
        Income::factory()->for($property)->rent(2000)->create();
        // $2,400 this year => $200/mo, $2,400/yr.
        PropertyTax::factory()->for($property)->create([
            'year' => (int) now()->year,
            'amount' => 2400,
        ]);
        // A prior year's tax must not affect the current cashflow.
        PropertyTax::factory()->for($property)->create([
            'year' => (int) now()->subYear()->year,
            'amount' => 9999,
        ]);

        $summary = $this->summarize($property);

        $this->assertSame(200.0, $summary['monthly_expenses']);
        $this->assertSame(2400.0, $summary['annual_expenses']);
    }

    public function test_current_rent_ignores_expired_rent_entries(): void
    {
        $property = Property::factory()->create();
        // Expired rent last year — should not count as current rent.
        Income::factory()->for($property)->rent(3000)->create([
            'start_date' => now()->subYears(2)->toDateString(),
            'end_date' => now()->subYear()->toDateString(),
        ]);
        // Active open-ended rent.
        Income::factory()->for($property)->rent(2000)->create();

        $this->assertSame(2000.0, $property->fresh('incomes')->currentMonthlyRent());
    }
}
