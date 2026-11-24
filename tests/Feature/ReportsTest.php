<?php

namespace Tests\Feature;

use App\Models\PostProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    public function test_period_changes_what_the_charts_cover(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        PostProperty::factory()->create(['property_type' => 'Villa', 'city' => 'Pune', 'created_at' => now()->subDays(3)]);
        PostProperty::factory()->create(['property_type' => 'Plot', 'city' => 'Goa', 'created_at' => now()->subMonths(9)]);

        $this->actingAs($admin)->get(route('reports.index', ['range' => '30d']))->assertOk()
            ->assertViewHas('monthlyLabels', fn ($l) => $l->count() === 30)
            ->assertViewHas('cityLabels', fn ($l) => $l->all() === ['Pune'])
            ->assertSee('Daily Listings')->assertSee('<option value="30d" selected>', false);
        $this->actingAs($admin)->get(route('reports.index', ['range' => '1y']))
            ->assertViewHas('monthlyLabels', fn ($l) => $l->count() === 12)
            ->assertViewHas('cityLabels', fn ($l) => $l->contains('Goa') && $l->contains('Pune'));
        $this->actingAs($admin)->get(route('reports.index', ['range' => 'nonsense']))->assertViewHas('range', '6m');
    }

    public function test_export_downloads_the_report_for_the_period(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        PostProperty::factory()->create(['property_type' => 'Villa', 'city' => 'Pune', 'created_at' => now()->subDays(3)]);

        $response = $this->actingAs($admin)->get(route('reports.export', ['range' => '30d']))->assertOk();
        $this->assertStringContainsString('attachment; filename=cmnhousing-report-last-30-days-', $response->headers->get('content-disposition'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Last 30 Days', $csv);
        $this->assertStringContainsString('"New listings",1', $csv);
        $this->assertStringContainsString('Villa,1,100', $csv);
        $this->assertStringContainsString('Pune,1', $csv);
        $this->actingAs($admin)->get(route('reports.index'))->assertSee(route('reports.export', ['range' => '6m']), false);
    }
}
