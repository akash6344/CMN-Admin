<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_logs_page(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin', 'name' => 'Audit Admin']);
        AuditLog::factory()->create([
            'user_id' => $admin->id,
            'action' => 'property.approved',
            'description' => 'Property P1001 approved.',
        ]);

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit Logs')
            ->assertSee('property.approved')
            ->assertSee('Property P1001 approved.')
            ->assertSee('Audit Admin');
    }

    public function test_audit_logs_export_as_csv_with_the_current_filters(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin', 'name' => 'Audit Admin']);
        AuditLog::factory()->create(['user_id' => $admin->id, 'action' => 'listing.approved', 'description' => 'Listing P1 approved.']);
        AuditLog::factory()->create(['user_id' => $admin->id, 'action' => 'user.created', 'description' => 'User Bob created.']);

        $this->actingAs($admin)->get(route('audit-logs.index'))->assertSee(route('audit-logs.export'));

        $csv = $this->actingAs($admin)->get(route('audit-logs.export', ['action' => 'listing.approved']))
            ->assertOk()->assertDownload()->streamedContent();
        $this->assertStringContainsString('Listing P1 approved.', $csv);
        $this->assertStringContainsString('Audit Admin', $csv);
        $this->assertStringNotContainsString('User Bob created.', $csv);
    }
}
