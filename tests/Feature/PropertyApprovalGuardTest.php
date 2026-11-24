<?php

namespace Tests\Feature;

use App\Models\Dashboard\PropertyReview;
use App\Models\Housing\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

class PropertyApprovalGuardTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['admin_role' => 'admin']);
    }

    private function listing(array $over = []): PropertyReview
    {
        $id = DB::connection('dashboard')->table('post_properties')->insertGetId($over + [
            'user_id' => 0, 'user_name' => 'Rahul Agent', 'user_role' => 'agent', 'user_email' => 'Rahul@Agent.test', 'property_title' => 'Agent Flat',
            'post_status' => 'pending', 'show_on_website' => 'no', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
        ]);

        return PropertyReview::query()->create(['kind' => 'listing', 'post_property_id' => $id, 'status' => 'ready_for_admin', 'submitted_to_admin_at' => now()->subHour()]);
    }

    public function test_project_review_cannot_be_approved_even_if_still_under_review(): void
    {
        $id = DB::connection('housing')->table('project_details')->insertGetId(['name' => 'Skyline', 'builder_name' => 'Sky', 'status' => 'under_review']);
        $r = PropertyReview::query()->create(['kind' => 'project', 'project_id' => $id, 'status' => 'ready_for_admin']);

        $this->actingAs($this->admin)->patch(route('properties.reviews.approve', $r))->assertSessionHasErrors('review');
        $this->assertSame('ready_for_admin', $r->fresh()->status);
        $this->assertSame('under_review', DB::connection('housing')->table('project_details')->find($id)->status);
    }

    public function test_withdrawn_listing_cannot_be_approved(): void
    {
        $r = $this->listing(['post_status' => 'Withdrawn']);

        $this->actingAs($this->admin)->patch(route('properties.reviews.approve', $r))->assertSessionHasErrors('review');
        $this->assertSame('ready_for_admin', $r->fresh()->status);
    }

    public function test_approving_an_agent_listing_notifies_the_agent(): void
    {
        Schema::connection('agent')->create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
        });
        DB::connection('agent')->table('users')->insert([['id' => 4, 'name' => 'Other', 'email' => 'x@agent.test'], ['id' => 7, 'name' => 'Rahul', 'email' => 'rahul@agent.test']]);
        $r = $this->listing();

        $this->actingAs($this->admin)->patch(route('properties.reviews.approve', $r))->assertSessionHasNoErrors();

        $n = AppNotification::query()->where('audience', 'agent')->sole();
        $this->assertSame(7, (int) $n->user_id);
        $this->assertSame('Your listing is live', $n->title);

        // a query goes to Operations only
        $q = $this->listing(['property_title' => 'Second']);
        $this->actingAs($this->admin)->patch(route('properties.reviews.query', $q), ['admin_note' => 'Check the price please']);
        $this->assertSame(1, AppNotification::query()->where('audience', 'agent')->count());
    }

    public function test_dashboard_cards_count_listings_only_with_a_read_only_brm_count(): void
    {
        $id = DB::connection('housing')->table('project_details')->insertGetId(['name' => 'Skyline', 'builder_name' => 'Sky', 'status' => 'under_review', 'created_at' => now(), 'updated_at' => now()]);
        DB::connection('housing')->table('project_details')->insert(['name' => 'Live Towers', 'builder_name' => 'Sky', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()]);
        PropertyReview::query()->create(['kind' => 'project', 'project_id' => $id, 'status' => 'ready_for_admin']);   // old Ops review — ignored
        $this->listing();
        $q = $this->listing(['property_title' => 'Queried Flat']);
        $q->update(['status' => 'admin_query']);

        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()
            ->assertSee(route('properties.index', ['status' => 'pending']))->assertSee('Agent Flat')->assertDontSee('>Skyline<', false)
            ->assertSee('Builder projects with BRM')->assertSee(route('properties.index', ['status' => 'builder', 'brm' => 'with_brm']))
            ->assertViewHas('withBrm', 1)
            ->assertViewHas('pendingProperties', fn ($rows) => $rows->every(fn ($it) => $it['kind'] === 'listing'))
            ->assertViewHas('weeklyMetrics', fn (array $m) => $m['awaiting']['total'] === 1 && $m['sent_back']['total'] === 1 && $m['with_ops']['total'] === 0 && $m['approved']['total'] === 0);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertSee('1 property waiting for your approval');
    }

    public function test_builder_projects_tab_shows_brm_state_reviewer_and_reason(): void
    {
        $at = now()->subDay();
        $row = fn (string $name, string $status, ?string $reason = null) => DB::connection('housing')->table('project_details')->insertGetId([
            'name' => $name, 'builder_name' => 'Sky', 'status' => $status, 'rejection_reason' => $reason,
            'reviewed_by' => $status === 'under_review' ? null : 'Meena BRM', 'reviewed_at' => $status === 'under_review' ? null : $at, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $row('Waiting Heights', 'under_review');
        $live = $row('Live Towers', 'published');
        $row('Edits Park', 'rejected', 'Add the RERA certificate');
        $rejected = $row('Rejected Residency', 'archived', 'Rejected: Fake RERA');
        $down = $row('Down Villas', 'rejected', 'Taken down: Expired RERA');
        $row('Draft Nest', 'draft');

        $page = $this->actingAs($this->admin)->get(route('properties.index', ['status' => 'builder']))->assertOk();
        foreach (['With BRM (under review)', 'Live', 'Changes requested', 'Rejected', 'Taken down', 'By Meena BRM · '.$at->format('j M Y'), 'Open in BRM'] as $text) {
            $page->assertSee($text);
        }
        $page->assertDontSee('Draft Nest')->assertDontSee('properties.reviews', false);
        $this->actingAs($this->admin)->get(route('properties.index', ['status' => 'builder', 'brm' => 'taken_down']))
            ->assertSee('Down Villas')->assertDontSee('Edits Park')->assertDontSee('Live Towers');
        $this->actingAs($this->admin)->get(route('properties.index', ['status' => 'builder', 'brm' => 'changes']))
            ->assertSee('Edits Park')->assertDontSee('Down Villas');

        $this->actingAs($this->admin)->get(route('properties.show', 'project-'.$rejected))->assertOk()
            ->assertSee('Reviewed by Meena BRM')->assertSee('Rejected: Fake RERA')->assertSee('BRM Review')
            ->assertDontSee('Approve &amp; Publish', false)->assertDontSee('Your Decision');
        $this->actingAs($this->admin)->get(route('properties.show', 'project-'.$down))->assertOk()->assertSee('Taken down')->assertSee('Expired RERA');
        $this->actingAs($this->admin)->get(route('properties.show', 'project-'.$live))->assertOk()->assertSee('Live')->assertSee('Meena BRM');
    }
}
