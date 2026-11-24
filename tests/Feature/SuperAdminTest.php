<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

/** Super admins ("super admin" on the Users page) sign in like admins; only they manage the Team once one exists. */
class SuperAdminTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    public function test_super_admin_can_sign_in_and_use_the_portal(): void
    {
        foreach (['super admin', 'super_admin', 'Super Admin'] as $i => $role) {
            $u = User::factory()->create(['admin_role' => $role, 'email' => "super{$i}@example.test", 'password' => Hash::make('secret-pass')]);
            $this->assertTrue($u->isAdmin());
            $this->assertTrue($u->isSuperAdmin());
        }

        $this->post(route('login.store'), ['login' => 'super0@example.test', 'password' => 'secret-pass'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('properties.index'))->assertOk();
        $this->get(route('settings.index'))->assertOk()->assertSee('Super Admin')->assertSee('Add an admin');
    }

    public function test_only_super_admins_manage_the_team_once_one_exists(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin', 'name' => 'Plain Admin']);
        $other = User::factory()->create(['admin_role' => 'admin', 'name' => 'Other Admin']);
        $new = ['name' => 'New Admin', 'email' => 'new@example.test', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass'];

        // nobody is a super admin yet → admins keep team management (no lock-out)
        $this->assertTrue($admin->canManageTeam());
        $this->actingAs($admin)->get(route('settings.index'))->assertSee('Add an admin')->assertSee('Deactivate');

        $super = User::factory()->create(['admin_role' => 'super admin', 'name' => 'Sana Super']);
        $this->assertFalse($admin->fresh()->canManageTeam());
        $this->actingAs($admin)->get(route('settings.index'))->assertOk()->assertSee('Sana Super')
            ->assertSee('Only a super admin can add, deactivate or reactivate admins.')->assertDontSee('Add an admin')->assertDontSee('>Deactivate<', false);
        $this->actingAs($admin)->post(route('settings.team.store'), $new)->assertSessionHasErrors('team');
        $this->actingAs($admin)->patch(route('settings.team.toggle', $other))->assertSessionHasErrors('team');
        $this->assertTrue($other->fresh()->isActive());
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);

        // Users page can't be used to get around it (e.g. promoting yourself)
        $this->actingAs($admin)->put(route('users.update', $admin), ['name' => 'Plain Admin', 'email' => $admin->email, 'mobile' => '9000000001', 'admin_role' => 'super admin'])
            ->assertSessionHasErrors('user');
        $this->assertFalse($admin->fresh()->isSuperAdmin());
        $this->actingAs($admin)->patch(route('users.toggle-status', $other))->assertSessionHasErrors('user');
        $this->actingAs($admin)->delete(route('users.destroy', $super))->assertSessionHasErrors('user');
        $this->assertModelExists($super);

        // the super admin can
        $this->actingAs($super)->post(route('settings.team.store'), $new)->assertSessionHas('success');
        $this->actingAs($super)->patch(route('settings.team.toggle', $other))->assertSessionHas('success');
        $this->assertFalse($other->fresh()->isActive());

        // an inactive super admin doesn't lock the team: admins take it over again
        $super->update(['status' => 'Inactive']);
        $this->assertTrue($admin->fresh()->canManageTeam());
    }
}
