<?php

namespace Tests\Feature;

use App\Enums\AmountType;
use App\Enums\Frequency;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_lists_the_current_users_properties(): void
    {
        $me = User::factory()->create();
        $mine = Property::factory()->for($me)->create(['name' => 'Mine']);
        Property::factory()->create(['name' => 'Theirs']);

        $this->actingAs($me)
            ->get(route('properties.index'))
            ->assertInertia(fn ($page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $mine->id));
    }

    public function test_a_user_cannot_view_another_users_property(): void
    {
        $property = Property::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('properties.show', $property))
            ->assertForbidden();
    }

    public function test_a_user_cannot_update_or_delete_another_users_property(): void
    {
        $property = Property::factory()->create(['name' => 'Original']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->put(route('properties.update', $property), ['name' => 'Hacked'])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('properties.destroy', $property))
            ->assertForbidden();

        $this->assertDatabaseHas('properties', ['id' => $property->id, 'name' => 'Original']);
    }

    public function test_a_user_cannot_attach_an_expense_to_another_users_property(): void
    {
        $property = Property::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('expenses.store'), [
                'property_id' => $property->id,
                'label' => 'Sneaky',
                'frequency' => Frequency::Monthly->value,
                'amount_type' => AmountType::Fixed->value,
                'amount' => 100,
            ])
            ->assertSessionHasErrors('property_id');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('properties.index'))->assertRedirect(route('login'));
    }
}
