<?php

namespace Tests\Feature;

use App\Enums\Frequency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Property;
use App\Models\PropertyTax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpensesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_current_users_expenses_with_monthly_equivalents(): void
    {
        $me = User::factory()->create();
        $property = Property::factory()->for($me)->create();
        Income::factory()->for($property)->rent(2000)->create();
        Expense::factory()->for($property)->percentage(8)->create([
            'label' => 'Management',
            'frequency' => Frequency::Monthly,
        ]);

        // Another user's expense must not appear.
        Expense::factory()->create(['label' => 'Someone else']);

        $this->actingAs($me)
            ->get(route('expenses.index'))
            ->assertInertia(fn ($page) => $page
                ->component('expenses/index')
                ->has('expenses', 1)
                ->where('expenses.0.label', 'Management')
                ->where('expenses.0.monthly_amount', 160)); // 8% of $2,000 (JSON drops the .0)
    }

    public function test_one_time_expense_shows_an_amortized_monthly_equivalent(): void
    {
        $me = User::factory()->create();
        $property = Property::factory()->for($me)->create();
        Expense::factory()->for($property)->create([
            'label' => 'Leasing fee',
            'frequency' => Frequency::OneTime,
            'amount' => 1200,
        ]);

        $this->actingAs($me)
            ->get(route('expenses.index'))
            ->assertInertia(fn ($page) => $page
                // $1,200 one-time amortizes to $100/mo in the display column.
                ->where('expenses.0.monthly_amount', 100));
    }

    public function test_property_tax_accrues_linearly_toward_its_bill_date(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 7));

        $me = User::factory()->create();
        $property = Property::factory()->for($me)->create();
        // Accrual window 2025-12-31 .. 2026-12-31 (365 days); today is 188 days in.
        // $3,650 accrues at $10/day -> $1,880 by 2026-07-07.
        PropertyTax::factory()->for($property)->create([
            'year' => 2026,
            'amount' => 3650,
            'bill_date' => '2026-12-31',
        ]);

        $this->actingAs($me)
            ->get(route('expenses.index'))
            ->assertInertia(fn ($page) => $page
                ->has('propertyTaxes', 1)
                ->where('propertyTaxes.0.accrued', 1880));
    }

    public function test_property_tax_without_a_bill_date_has_no_accrual(): void
    {
        $me = User::factory()->create();
        $property = Property::factory()->for($me)->create();
        PropertyTax::factory()->for($property)->create([
            'year' => (int) now()->year,
            'amount' => 4200,
            'bill_date' => null,
        ]);

        $this->actingAs($me)
            ->get(route('expenses.index'))
            ->assertInertia(fn ($page) => $page->where('propertyTaxes.0.accrued', null));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('expenses.index'))->assertRedirect(route('login'));
    }
}
