<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Dashboard\PropertyReview;
use App\Models\Housing\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

class AdminInboxTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    public function test_bell_and_page_read_admin_notifications_and_mark_read(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $handover = AppNotification::send('admin', 'listing', 'Operations sent P014 for approval', 'Score 92', '/properties', 'info');
        AppNotification::send('admin', 'ticket_escalated', 'Critical ticket T-9', 'Lift broken', '/complaints/9', 'danger');
        AppNotification::send('ops', 'listing', 'Not for admin', null, '/x');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertSee('Operations sent P014 for approval')->assertSee('Critical ticket T-9')->assertDontSee('Not for admin')->assertSee('2 unread');

        // turning complaints off hides that kind from the bell, not from the page
        $admin->update(['notification_preferences' => ['complaints' => false]]);
        $this->actingAs($admin->fresh())->get(route('dashboard'))->assertDontSee('Critical ticket T-9');
        $this->actingAs($admin->fresh())->get(route('notifications.index'))->assertOk()->assertSee('Critical ticket T-9')->assertSee('Unread (2)', false);

        $this->actingAs($admin)->get(route('notifications.open', $handover->id))->assertRedirect(url('/properties'));
        $this->assertNotNull($handover->fresh()->read_at);
        $ops = AppNotification::query()->where('audience', 'ops')->value('id');
        $this->actingAs($admin)->get(route('notifications.open', $ops))->assertNotFound();

        $this->actingAs($admin)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, AppNotification::query()->where('audience', 'admin')->whereNull('read_at')->count());
        $this->assertNull(AppNotification::query()->where('audience', 'ops')->value('read_at'));
        $this->assertTrue(AuditLog::query()->where('action', 'notifications.read_all')->exists());
    }

    public function test_bell_shows_only_the_waiting_count_not_each_ops_handover(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $id = DB::connection('dashboard')->table('post_properties')->insertGetId(['property_title' => 'Flat', 'post_status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        PropertyReview::query()->create(['kind' => 'listing', 'post_property_id' => $id, 'status' => 'ready_for_admin']);
        AppNotification::send('admin', 'listing', 'New property ready for approval', 'P001 · Flat', '/properties', 'info');
        AppNotification::send('admin', 'ticket_escalated', 'Critical ticket T-9', 'Lift broken', '/complaints/9', 'danger');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertSee('1 property waiting for your approval')->assertDontSee('New property ready for approval')
            ->assertSee('Critical ticket T-9')->assertSee('1 unread');

        // with property-approval alerts off, neither shows in the bell; the Notifications page keeps everything
        $admin->update(['notification_preferences' => ['property_approvals' => false]]);
        $this->actingAs($admin->fresh())->get(route('dashboard'))->assertDontSee('waiting for your approval')->assertDontSee('New property ready for approval');
        $this->actingAs($admin->fresh())->get(route('notifications.index'))->assertSee('New property ready for approval');
    }
}
