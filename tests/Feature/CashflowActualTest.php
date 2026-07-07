<?php

namespace Tests\Feature;

use App\Enums\AmountType;
use App\Enums\Frequency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Property;
use App\Models\PropertyTax;
use App\Models\User;
use App\Services\CashflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CashflowActualTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Freeze "today" so year math is deterministic.
        $this->travelTo(Carbon::create(2026, 7, 6));
    }

    public function test_available_years_span_rent_periods_newest_first(): void
    {
        $user = User::factory()->create();
        $property = Property::factory()->for($user)->create();
        Income::factory()->for($property)->rent(1800)->create([
            'start_date' => '2024-01-01',
            'end_date' => '2025-12-31',
        ]);
        Income::factory()->for($property)->rent(2000)->create([
            'start_date' => '2026-01-01',
            'end_date' => null, // ongoing -> through the current year (2026)
        ]);

        $years = app(CashflowService::class)->availableYears($user->load('properties.incomes'));

        $this->assertSame([2026, 2025, 2024], $years);
    }

    public function test_vacant_months_reduce_actual_rent(): void
    {
        $property = Property::factory()->create();
        // Rent starts June 2026 -> Jan–May are vacant (5 months), Jun–Dec occupied (7).
        Income::factory()->for($property)->rent(2000)->create([
            'start_date' => '2026-06-01',
            'end_date' => null,
        ]);
        Expense::factory()->for($property)->create([
            'label' => 'Insurance',
            'frequency' => Frequency::Monthly,
            'amount_type' => AmountType::Fixed,
            'amount' => 100,
        ]);
        PropertyTax::factory()->for($property)->create(['year' => 2026, 'amount' => 3600]);

        $actual = app(CashflowService::class)
            ->actualForProperty($property->fresh(['incomes', 'expenses', 'propertyTaxes']), 2026);

        $this->assertSame('7 months', $actual['occupied_label']);
        $this->assertSame('5 months', $actual['vacant_label']);
        // 7 months x $2,000 = $14,000 collected (not the full $24,000).
        $this->assertSame(14000.0, $actual['income_total']);
        // Insurance $1,200/yr + property tax $3,600.
        $this->assertSame(4800.0, $actual['expense_total']);
        $this->assertSame(9200.0, $actual['net']);
    }

    public function test_partial_month_gap_prorates_rent_and_reads_in_weeks(): void
    {
        $property = Property::factory()->create();
        // Tenant 1 to Jun 15, Tenant 2 from Jul 1 -> Jun 16–30 (15 days) vacant.
        Income::factory()->for($property)->rent(3000)->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-15',
        ]);
        Income::factory()->for($property)->rent(3000)->create([
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $actual = app(CashflowService::class)
            ->actualForProperty($property->fresh(['incomes', 'expenses', 'propertyTaxes']), 2026);

        // 11 full months + 15 occupied days in June; the 15-day gap reads the same.
        $this->assertSame('11 months, 2 weeks, 1 day', $actual['occupied_label']);
        $this->assertSame('2 weeks, 1 day', $actual['vacant_label']);
        // 11 * $3,000 + half of June ($1,500) = $34,500.
        $this->assertSame(34500.0, $actual['income_total']);
    }

    public function test_estimated_property_tax_is_labeled_in_line_items(): void
    {
        $property = Property::factory()->create();
        Income::factory()->for($property)->rent(2000)->create(['start_date' => '2026-01-01']);
        PropertyTax::factory()->for($property)->create([
            'year' => 2026,
            'amount' => 4200,
            'estimated' => true,
        ]);

        $actual = app(CashflowService::class)
            ->actualForProperty($property->fresh(['incomes', 'expenses', 'propertyTaxes']), 2026);

        $taxLine = collect($actual['expense_lines'])->firstWhere('detail', '2026');
        $this->assertSame('Property Tax - Estimated', $taxLine['label']);
        $this->assertSame(4200.0, $taxLine['amount']);
    }

    public function test_expense_lines_include_a_property_tax_line_for_the_year(): void
    {
        $property = Property::factory()->create();
        Income::factory()->for($property)->rent(2000)->create(['start_date' => '2026-01-01']);
        PropertyTax::factory()->for($property)->create(['year' => 2026, 'amount' => 4200]);

        $actual = app(CashflowService::class)
            ->actualForProperty($property->fresh(['incomes', 'expenses', 'propertyTaxes']), 2026);

        $taxLine = collect($actual['expense_lines'])->firstWhere('label', 'Property tax');
        $this->assertNotNull($taxLine);
        $this->assertSame(4200.0, $taxLine['amount']);
        $this->assertSame('2026', $taxLine['detail']);
    }

    public function test_index_defaults_to_current_year_and_scopes_to_owner(): void
    {
        $me = User::factory()->create();
        $property = Property::factory()->for($me)->create();
        Income::factory()->for($property)->rent(2000)->create(['start_date' => '2026-01-01']);

        // Another user's data must not appear.
        $their = Property::factory()->create();
        Income::factory()->for($their)->rent(9999)->create(['start_date' => '2026-01-01']);

        $this->actingAs($me)
            ->get(route('cashflow.actual'))
            ->assertInertia(fn ($page) => $page
                ->component('cashflow-actual/index')
                ->where('year', 2026)
                ->where('availableYears', [2026])
                ->has('properties', 1));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('cashflow.actual'))->assertRedirect(route('login'));
    }
}
