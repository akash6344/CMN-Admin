<?php

namespace Tests\Feature;

use App\Models\Dashboard\PropertyReview;
use App\Models\Housing\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

/**
 * Admin's half of the review flow, inside Properties: Operations (CMN-Emp) sends owner / agent listings,
 * Admin approves or raises a query back to Operations. There is no separate approvals page.
 * Builder projects are reviewed by BRM — Admin sees them view only in the "Builder projects" tab.
 */
class PropertyApprovalTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['admin_role' => 'admin', 'name' => 'Priya Admin']);
    }

    private function listingReview(string $status = 'ready_for_admin', string $title = '3 BHK Apartment in Andheri West'): PropertyReview
    {
        $id = DB::connection('dashboard')->table('post_properties')->insertGetId([
            'user_id' => 7, 'user_name' => 'Ramesh Verma', 'user_role' => 'owner', 'city' => 'Mumbai', 'location' => 'Andheri West', 'property_type' => 'Apartment', 'property_for' => 'Sale',
            'property_title' => $title, 'price' => '1.2', 'price_text' => 'Cr', 'post_status' => 'pending', 'show_on_website' => 'no', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $review = PropertyReview::query()->create([
            'kind' => 'listing', 'post_property_id' => $id, 'status' => $status, 'score' => 88, 'reviewer_name' => 'Rajesh Kumar', 'notes' => 'Called owner, documents match.',
            'checks' => ['details' => true, 'documents' => false, 'market' => null], 'manual_checks' => ['kyc' => true], 'submitted_to_admin_at' => now()->subHour(),
        ]);
        $review->log('note', 'Rajesh Kumar', 'Called owner, documents match.');
        $review->log('ready_for_admin', 'Rajesh Kumar');

        return $review;
    }

    private function project(array $over = []): int
    {
        $id = DB::connection('housing')->table('project_details')->insertGetId($over + [
            'user_id' => 3, 'name' => 'Skyline Heights', 'builder_name' => 'Skyline Developers', 'location' => 'Gachibowli, Hyderabad', 'project_type' => 'Residential Apartment',
            'project_status' => 'Under Construction', 'rera_number' => 'P02400012345', 'status' => 'under_review', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::connection('housing')->table('unit_configurations')->insert(['project_detail_id' => $id, 'unit_type' => '2 BHK', 'built_up_area' => 1200, 'price' => 9000000]);

        return $id;
    }

    /** An old review Operations opened for a builder project before BRM took project reviews over. */
    private function projectReview(): PropertyReview
    {
        $id = $this->project();

        return PropertyReview::query()->create([
            'kind' => 'project', 'project_id' => $id, 'status' => 'ready_for_admin', 'score' => 100, 'reviewer_name' => 'Rajesh Kumar',
            'checks' => ['details' => true, 'rera' => true], 'manual_checks' => ['rera_verified' => true], 'submitted_to_admin_at' => now()->subHour(),
        ]);
    }

    public function test_properties_shows_what_operations_sent_with_their_review_and_comments(): void
    {
        $l = $this->listingReview();
        $this->projectReview();
        $this->listingReview('in_review', 'Still with ops');

        // listings only: a builder project (even with an old Operations review) is never pending Admin's approval
        $this->actingAs($this->admin)->get(route('properties.index', ['status' => 'pending']))->assertOk()
            ->assertSee('Pending Approval')->assertSee('3 BHK Apartment in Andheri West')->assertDontSee('Skyline Heights')->assertSee('₹1.2 Cr')
            ->assertSee('Approve')->assertSee('openRejectProperty')->assertDontSee('Still with ops');
        $this->actingAs($this->admin)->get(route('properties.index'))->assertSee('Andheri West')->assertDontSee('Skyline Heights');
        $this->actingAs($this->admin)->get(route('properties.index', ['status' => 'with_ops']))->assertSee('Still with ops')->assertDontSee('Skyline Heights');
        $this->actingAs($this->admin)->get(route('properties.index', ['status' => 'builder']))->assertOk()
            ->assertSee('Builder projects (reviewed by BRM)')->assertSee('Skyline Heights')->assertSee('PR001')->assertSee('With BRM (under review)')
            ->assertSee('Open in BRM')->assertDontSee('Andheri West')->assertDontSee('openRejectProperty(this)', false);
        $this->actingAs($this->admin)->get(route('properties.index', ['source' => 'builder']))->assertRedirect(route('properties.index', ['status' => 'builder']));
        $this->actingAs($this->admin)->get(route('properties.index', ['source' => 'owner']))->assertSee('Andheri West')->assertDontSee('Skyline Heights');

        // View → the full property page with Operations' review, comments and the decision
        $this->actingAs($this->admin)->get(route('properties.show', 'listing-'.$l->post_property_id))->assertOk()
            ->assertSee('Pending your approval')->assertSee('Called owner, documents match.')->assertSee('Owner KYC verified')
            ->assertSee('Comments &amp; Activity', false)->assertSee('Sent to Admin for approval')
            ->assertSee('Approve &amp; Publish', false)->assertSee('Reject');
        $this->actingAs($this->admin)->get(route('properties.show', 'project-1'))->assertOk()
            ->assertSee('Builder project')->assertSee('BRM Review')->assertSee('₹90 L')->assertSee('Units &amp; Pricing', false)
            ->assertSee('Open in BRM Project Approvals')->assertSee('http://localhost:8090/project-approvals/1')
            ->assertDontSee('Approve &amp; Publish', false)->assertDontSee('Operations Review');
        $this->actingAs($this->admin)->get(route('properties.index', ['item' => 'project-1']))->assertRedirect(route('properties.show', 'project-1'));
        $this->actingAs($this->admin)->get(route('properties.show', 'listing-999'))->assertNotFound();

        // no separate approvals or projects pages; the count sits on Properties
        $this->assertFalse(Route::has('ops-approvals.index'));
        $this->assertFalse(Route::has('projects.index'));
        $this->actingAs($this->admin)->get('/approvals')->assertRedirect('/properties?status=pending');
        $this->actingAs($this->admin)->get('/projects')->assertRedirect('/properties?status=builder');
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('1 awaiting your approval')->assertDontSee('nav-projects', false);
    }

    public function test_approving_a_listing_publishes_it(): void
    {
        $r = $this->listingReview();
        $this->actingAs($this->admin)->patch(route('properties.reviews.approve', $r))
            ->assertRedirect(route('properties.show', 'listing-'.$r->post_property_id));

        $r->refresh();
        $this->assertSame('approved', $r->status);
        $this->assertSame('Priya Admin', $r->decided_by);
        $this->assertSame('Approved', $r->listing->post_status);
        $this->assertSame('Show', $r->listing->show_on_website);
        $this->assertSame(1, DB::connection('dashboard')->table('notifications')->where('user_id', 7)->count());
        $this->assertTrue(AppNotification::query()->where('audience', 'ops')->where('title', 'Admin approved P001')->exists());
        $this->assertDatabaseHas('audit_logs', ['action' => 'listing.approved']);
        $this->actingAs($this->admin)->get(route('properties.show', 'listing-'.$r->post_property_id))->assertSee('Approved — live on the website')->assertSee('Approved by Priya Admin');

        $this->actingAs($this->admin)->patch(route('properties.reviews.query', $r), ['admin_note' => 'Too late'])->assertSessionHasErrors('review');
    }

    public function test_admin_refuses_to_approve_or_query_a_builder_project_review(): void
    {
        $r = $this->projectReview();
        $this->actingAs($this->admin)->patch(route('properties.reviews.approve', $r))
            ->assertSessionHasErrors(['review' => 'Builder projects are reviewed by the BRM team in the BRM portal — Admin can’t approve or query them.']);
        $this->actingAs($this->admin)->patch(route('properties.reviews.query', $r), ['admin_note' => 'Possession date looks wrong.'])->assertSessionHasErrors('review');

        $this->assertSame('ready_for_admin', $r->fresh()->status);
        $this->assertSame('under_review', DB::connection('housing')->table('project_details')->find($r->project_id)->status);   // builder not touched
        $this->assertSame(0, AppNotification::query()->count());
    }

    public function test_raise_a_query_sends_it_back_to_operations_and_shows_in_comments(): void
    {
        $r = $this->listingReview();
        $this->actingAs($this->admin)->patch(route('properties.reviews.query', $r), ['admin_note' => ''])->assertSessionHasErrors('admin_note');

        $this->actingAs($this->admin)->patch(route('properties.reviews.query', $r), ['admin_note' => 'Possession date looks wrong.']);
        $r->refresh();
        $this->assertSame('admin_query', $r->status);
        $this->assertSame('Possession date looks wrong.', $r->admin_note);
        $this->assertSame('pending', DB::connection('dashboard')->table('post_properties')->find($r->post_property_id)->post_status);   // owner not touched
        $this->assertSame(0, DB::connection('dashboard')->table('notifications')->count());
        $this->assertSame('Admin query on P001', AppNotification::query()->where('audience', 'ops')->value('title'));
        $this->assertStringContainsString('/ops/property-queue?review='.$r->id, AppNotification::query()->where('audience', 'ops')->value('link'));

        $this->actingAs($this->admin)->get(route('properties.index', ['status' => 'rejected']))->assertSee('3 BHK Apartment in Andheri West')->assertSee('Sent back to Ops')->assertSee('Possession date looks wrong.');
        $this->actingAs($this->admin)->get(route('properties.show', 'listing-'.$r->post_property_id))
            ->assertSee('Rejected by Admin — sent back to Operations')->assertSee('Rejected — with Operations')->assertDontSee('Approve &amp; Publish', false);
    }
}
