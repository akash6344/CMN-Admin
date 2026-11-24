<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

class LeadsTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    public function test_leads_page_lists_real_leads_with_filters_and_bargains_preview(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $projectId = DB::connection('housing')->table('project_details')->insertGetId(['name' => 'Skyline Heights', 'builder_name' => 'Skyline', 'status' => 'published']);
        DB::connection('housing')->table('leads')->insert([
            ['lead_code' => 'CMN-LD-1', 'name' => 'Asha Buyer', 'phone' => '9000000001', 'project_id' => $projectId, 'post_property_id' => null, 'status' => 'site_visit', 'source_platform' => 'agent_portal', 'created_at' => now(), 'updated_at' => now()],
            ['lead_code' => 'CMN-LD-2', 'name' => 'Ravi Seeker', 'phone' => '9000000002', 'project_id' => null, 'post_property_id' => 55, 'status' => 'new', 'source_platform' => 'dashboard', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($admin)->get(route('leads.index'))->assertOk()
            ->assertSee('Asha Buyer')->assertSee('Ravi Seeker')->assertSee('Skyline Heights')
            ->assertSee(route('properties.show', 'project-'.$projectId))->assertSee(route('properties.show', 'listing-55'))
            ->assertSee('Site Visit (1)', false)->assertSee('Preview · not built yet', false)
            ->assertDontSee('#L001')->assertDontSee('Rajesh K');

        $this->actingAs($admin)->get(route('leads.index', ['status' => 'new']))->assertSee('Ravi Seeker')->assertDontSee('Asha Buyer');
        $this->actingAs($admin)->get(route('leads.index', ['q' => 'Asha']))->assertSee('Asha Buyer')->assertDontSee('Ravi Seeker');
        $this->actingAs($admin)->get(route('leads.index', ['platform' => 'agent_portal']))->assertSee('Asha Buyer')->assertDontSee('Ravi Seeker');
    }
}
