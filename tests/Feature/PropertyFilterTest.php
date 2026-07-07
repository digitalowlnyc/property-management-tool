<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use App\Support\PropertyFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_a_property_scopes_list_views(): void
    {
        $me = User::factory()->create();
        $a = Property::factory()->for($me)->create(['name' => 'Alpha']);
        Property::factory()->for($me)->create(['name' => 'Beta']);

        $this->actingAs($me)
            ->put(route('selected-property.update'), ['property_id' => $a->id])
            ->assertRedirect();

        // The Properties tab always lists every property, surfacing the
        // selection so the UI can flag the active one.
        $this->actingAs($me)
            ->get(route('properties.index'))
            ->assertInertia(fn ($page) => $page
                ->has('properties', 2)
                ->where('propertyFilter.selected', $a->id));

        // Other list views are scoped to the selected property.
        $this->actingAs($me)
            ->get(route('income.index'))
            ->assertInertia(fn ($page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $a->id));
    }

    public function test_all_selection_shows_every_property(): void
    {
        $me = User::factory()->create();
        Property::factory()->for($me)->count(2)->create();

        $this->actingAs($me)
            ->get(route('properties.index'))
            ->assertInertia(fn ($page) => $page
                ->has('properties', 2)
                ->where('propertyFilter.selected', null));
    }

    public function test_a_user_cannot_select_another_users_property(): void
    {
        $me = User::factory()->create();
        $theirs = Property::factory()->create();

        $this->actingAs($me)
            ->put(route('selected-property.update'), ['property_id' => $theirs->id])
            ->assertSessionHasErrors('property_id');
    }

    public function test_a_stale_selection_falls_back_to_all(): void
    {
        $me = User::factory()->create();
        $a = Property::factory()->for($me)->create(['name' => 'Alpha']);
        Property::factory()->for($me)->create(['name' => 'Beta']);

        $this->actingAs($me)->put(route('selected-property.update'), ['property_id' => $a->id]);
        $a->delete();

        // The stale id is ignored -> everything shows again.
        $this->actingAs($me)
            ->get(route('properties.index'))
            ->assertInertia(fn ($page) => $page
                ->has('properties', 1)
                ->where('propertyFilter.selected', null));

        $this->assertNull(PropertyFilter::selectedId($me->fresh()));
    }
}
