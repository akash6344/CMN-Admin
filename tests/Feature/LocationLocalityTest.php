<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Locality;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationLocalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_locations_index_includes_localities_tab_data(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $state = State::query()->create(['state' => 'Karnataka', 'status' => 'Active']);
        $city = City::query()->create(['state_id' => $state->id, 'city' => 'Bangalore', 'status' => 'Active']);
        Locality::query()->create([
            'state_id' => $state->id,
            'city_id' => $city->id,
            'locality' => 'Indiranagar',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)
            ->get(route('locations.index'))
            ->assertOk()
            ->assertSee('Localities')
            ->assertSee('Indiranagar');
    }

    public function test_admin_can_create_update_and_delete_a_locality(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $state = State::query()->create(['state' => 'Telangana', 'status' => 'Active']);
        $city = City::query()->create(['state_id' => $state->id, 'city' => 'Hyderabad', 'status' => 'Active']);

        $this->actingAs($admin)
            ->post(route('locations.localities.store'), [
                'state_id' => $state->id,
                'city_id' => $city->id,
                'locality' => 'Gachibowli',
                'status' => 'Active',
            ])
            ->assertRedirect();

        $locality = Locality::query()->where('locality', 'Gachibowli')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('locations.localities.update', $locality), [
                'state_id' => $state->id,
                'city_id' => $city->id,
                'locality' => 'Hitech City',
                'status' => 'Inactive',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('localities', [
            'id' => $locality->id,
            'locality' => 'Hitech City',
            'status' => 'Inactive',
        ]);

        $this->actingAs($admin)
            ->delete(route('locations.localities.destroy', $locality))
            ->assertRedirect();

        $this->assertDatabaseMissing('localities', ['id' => $locality->id]);
    }

    public function test_locality_city_must_belong_to_selected_state(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $karnataka = State::query()->create(['state' => 'Karnataka', 'status' => 'Active']);
        $telangana = State::query()->create(['state' => 'Telangana', 'status' => 'Active']);
        $hyderabad = City::query()->create(['state_id' => $telangana->id, 'city' => 'Hyderabad', 'status' => 'Active']);

        $this->actingAs($admin)
            ->from(route('locations.index'))
            ->post(route('locations.localities.store'), [
                'state_id' => $karnataka->id,
                'city_id' => $hyderabad->id,
                'locality' => 'Mismatch Area',
                'status' => 'Active',
            ])
            ->assertRedirect(route('locations.index'))
            ->assertSessionHasErrors('city_id');
    }
}
