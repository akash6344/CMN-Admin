<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_a_user(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'seller',
            'name' => 'Old Name',
            'mobile' => '9111111111',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => 'New Name',
                'email' => $user->email,
                'mobile' => $user->mobile,
                'city' => 'Hyderabad',
                'role' => 'seller',
                'status' => 'Active',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'city' => 'Hyderabad',
        ]);
    }

    public function test_admin_can_block_and_activate_a_user(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $user = User::factory()->create(['role' => 'buyer', 'status' => 'Active', 'mobile' => '9222222222']);

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $user))
            ->assertRedirect();

        $this->assertSame('Inactive', $user->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $user))
            ->assertRedirect();

        $this->assertSame('Active', $user->fresh()->status);
    }

    public function test_admin_can_delete_a_user_but_not_themselves(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $user = User::factory()->create(['role' => 'agent', 'mobile' => '9333333333']);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors('user');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
