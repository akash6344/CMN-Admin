<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Housing\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['admin_role' => 'admin', 'name' => 'Priya Admin', 'mobile' => '9876543210', 'password' => Hash::make('old-password')]);
    }

    public function test_settings_page_shows_real_sections_and_team(): void
    {
        User::factory()->create(['admin_role' => 'admin', 'name' => 'Second Admin']);
        User::factory()->create(['admin_role' => 'hr', 'name' => 'Hr Person']);

        $this->actingAs($this->admin)->get(route('settings.index'))->assertOk()
            ->assertSee('Profile')->assertSee('9876543210')->assertSee('Change password')
            ->assertSee('Properties awaiting approval')->assertSee('Second Admin')->assertDontSee('Hr Person')
            ->assertDontSee('Two-Factor Authentication')->assertDontSee('Export Users');
    }

    public function test_profile_and_password_save_from_settings_with_audit(): void
    {
        $this->actingAs($this->admin)->from(route('settings.index'))
            ->put(route('profile.update'), ['name' => 'Priya S', 'email' => 'priya@example.test', 'mobile' => '9000000001'])
            ->assertRedirect(route('settings.index'))->assertSessionHas('success');
        $this->assertSame('Priya S', $this->admin->fresh()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'profile.updated', 'user_id' => $this->admin->id]);

        $this->actingAs($this->admin)->from(route('settings.index'))
            ->put(route('profile.password.update'), ['current_password' => 'old-password', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->actingAs($this->admin)->from(route('settings.index'))
            ->put(route('profile.password.update'), ['current_password' => 'old-password', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
            ->assertRedirect(route('settings.index'));
        $this->assertTrue(Hash::check('new-password-1', $this->admin->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'profile.password_changed']);
    }

    public function test_notification_preferences_save_and_drive_the_bell(): void
    {
        SupportTicket::query()->create([
            'code' => 'T-1', 'builder_name' => 'Skyline', 'subject' => 'Help', 'category' => 'technical', 'priority' => 'high', 'status' => 'escalated', 'raised_by' => 'builder',
        ]);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertSee('1 escalated complaint');

        $this->actingAs($this->admin)->put(route('settings.notifications.update'), ['alerts' => ['property_approvals', 'bogus']])->assertSessionHasErrors('alerts.1');

        $this->actingAs($this->admin)->put(route('settings.notifications.update'), ['alerts' => ['property_approvals']])->assertRedirect();
        $prefs = $this->admin->fresh()->notification_preferences;
        $this->assertTrue($prefs['property_approvals']);
        $this->assertFalse($prefs['complaints']);
        $this->assertFalse($this->admin->fresh()->wantsAlert('complaints'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.notifications_updated']);

        $this->actingAs($this->admin->fresh())->get(route('dashboard'))->assertDontSee('1 escalated complaint');
    }

    public function test_team_add_and_deactivate_admins(): void
    {
        $this->actingAs($this->admin)->post(route('settings.team.store'), ['name' => 'New Admin', 'email' => 'bad', 'password' => 'x'])
            ->assertSessionHasErrorsIn('team', ['email', 'password']);

        $this->actingAs($this->admin)->post(route('settings.team.store'), [
            'name' => 'New Admin', 'email' => 'new@example.test', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertRedirect()->assertSessionHas('success');
        $new = User::query()->where('email', 'new@example.test')->sole();
        $this->assertTrue($new->isAdmin());
        $this->assertTrue($new->isActive());
        $this->assertDatabaseHas('audit_logs', ['action' => 'team.admin_added']);

        $this->actingAs($this->admin)->patch(route('settings.team.toggle', $new))->assertRedirect();
        $this->assertFalse($new->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['action' => 'team.admin_deactivated']);

        // deactivated admins can't sign in, and an open session is ended
        Auth::logout();
        $this->post(route('login.store'), ['login' => 'new@example.test', 'password' => 'secret-pass'])->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->actingAs($new->fresh())->get(route('dashboard'))->assertRedirect(route('login'));

        // can't deactivate yourself; non-admins aren't team members
        $this->actingAs($this->admin)->patch(route('settings.team.toggle', $this->admin))->assertSessionHasErrors('team');
        $this->assertTrue($this->admin->fresh()->isActive());
        $hr = User::factory()->create(['admin_role' => 'hr']);
        $this->actingAs($this->admin)->patch(route('settings.team.toggle', $hr))->assertNotFound();

        $this->actingAs($this->admin)->patch(route('settings.team.toggle', $new))->assertRedirect();
        $this->assertTrue($new->fresh()->isActive());
        $this->assertSame(1, AuditLog::query()->where('action', 'team.admin_reactivated')->count());
    }
}
