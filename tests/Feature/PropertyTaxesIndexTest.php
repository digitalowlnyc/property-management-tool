<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyTax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyTaxesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_computes_year_over_year_change_and_scopes_to_the_owner(): void
    {
        $me = User::factory()->create();
        $property = Property::factory()->for($me)->create();
        PropertyTax::factory()->for($property)->create(['year' => 2023, 'amount' => 4000]);
        PropertyTax::factory()->for($property)->create(['year' => 2024, 'amount' => 4400]);
        PropertyTax::factory()->for($property)->create(['year' => 2025, 'amount' => 4200]);

        // Another user's records must not leak in.
        PropertyTax::factory()->create(['year' => 2024, 'amount' => 1]);

        $this->actingAs($me)
            ->get(route('property-taxes.index'))
            ->assertInertia(fn ($page) => $page
                ->component('property-taxes/index')
                ->has('properties', 1)
                ->has('properties.0.taxes', 3)
                // First year has no prior year to compare against.
                ->where('properties.0.taxes.0.change_abs', null)
                ->where('properties.0.taxes.0.change_pct', null)
                // 2024: +$400 (+10%).
                ->where('properties.0.taxes.1.change_abs', 400)
                ->where('properties.0.taxes.1.change_pct', 10)
                // 2025: -$200 (-4.55%).
                ->where('properties.0.taxes.2.change_abs', -200)
                ->where('properties.0.taxes.2.change_pct', -4.55));
    }

    public function test_duplicate_year_for_a_property_is_rejected(): void
    {
        $me = User::factory()->create();
        $property = Property::factory()->for($me)->create();
        PropertyTax::factory()->for($property)->create(['year' => 2025, 'amount' => 4000]);

        $this->actingAs($me)
            ->post(route('property-taxes.store'), [
                'property_id' => $property->id,
                'year' => 2025,
                'amount' => 5000,
            ])
            ->assertSessionHasErrors('year');

        $this->assertDatabaseCount('property_taxes', 1);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('property-taxes.index'))->assertRedirect(route('login'));
    }
}
