<?php

namespace Tests\Feature;

use App\Models\PostProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

/** Properties shows the real website listings (CMN-Dashboard), not a separate Admin copy. */
class PropertyManagementTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    private function admin(): User
    {
        return User::factory()->create(['admin_role' => 'admin']);
    }

    public function test_admin_sees_website_listings_with_their_stage(): void
    {
        PostProperty::factory()->pending()->create(['property_title' => 'Indiranagar Flat', 'property_type' => 'Apartment / Flat', 'city' => 'Bangalore']);
        PostProperty::factory()->approved()->create(['property_title' => 'Live Villa']);

        $this->actingAs($this->admin())->get(route('properties.index'))->assertOk()
            ->assertSee('Total Listings')->assertSee('Pending Approval')->assertSee('Approved Listings')->assertSee('Rejected Listings')
            ->assertSee('Indiranagar Flat')->assertSee('Apartment / Flat')->assertSee('With Operations')
            ->assertSee('Live Villa')->assertSee('Approved')->assertDontSee('Request delete');
    }

    public function test_price_uses_the_website_unit(): void
    {
        PostProperty::factory()->pending()->create(['property_title' => 'Lakh Flat', 'price' => '75', 'price_text' => 'Lac']);
        PostProperty::factory()->pending()->create(['property_title' => 'Crore Villa', 'price' => '1.15', 'price_text' => 'Cr']);

        $this->actingAs($this->admin())->get(route('properties.index'))->assertSee('₹75 L')->assertSee('₹1.15 Cr');
    }

    public function test_filters_by_type_stage_and_search(): void
    {
        PostProperty::factory()->approved()->create(['property_title' => 'Plot Listing', 'property_type' => 'Residential Plot', 'city' => 'Chennai', 'location' => 'Adyar']);
        PostProperty::factory()->pending()->create(['property_title' => 'Villa Listing', 'property_type' => 'Independent House / Villa', 'city' => 'Pune']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('properties.index', ['property_type' => 'Residential Plot']))->assertSee('Plot Listing')->assertDontSee('Villa Listing');
        $this->actingAs($admin)->get(route('properties.index', ['status' => 'approved']))->assertSee('Plot Listing')->assertDontSee('Villa Listing');
        $this->actingAs($admin)->get(route('properties.index', ['status' => 'with_ops']))->assertSee('Villa Listing')->assertDontSee('Plot Listing');
        $this->actingAs($admin)->get(route('properties.index', ['q' => 'pune']))->assertSee('Villa Listing')->assertDontSee('Plot Listing');
    }

    public function test_admin_cannot_approve_or_reject_without_operations(): void
    {
        $this->assertFalse(Route::has('properties.approve'));
        $this->assertFalse(Route::has('properties.reject'));
        $p = PostProperty::factory()->pending()->create(['property_title' => 'Waiting Flat']);

        $this->actingAs($this->admin())->get(route('properties.show', 'listing-'.$p->id))->assertOk()
            ->assertSee('Operations is still reviewing this')->assertDontSee('Approve &amp; Publish', false)->assertDontSee('Request delete');
    }

    public function test_adding_a_property_sends_it_to_operations(): void
    {
        $this->actingAs($this->admin())->post(route('properties.store'), [])->assertSessionHasErrors(['property_title', 'user_name', 'city', 'price']);

        $this->actingAs($this->admin())->post(route('properties.store'), [
            'property_title' => 'Admin Added Flat', 'property_type' => 'Apartment', 'property_for' => 'Sale', 'user_name' => 'Sita Rao',
            'city' => 'Hyderabad', 'price' => '80', 'price_text' => 'Lac', 'bed' => '2',
        ])->assertRedirect();

        $p = PostProperty::query()->where('property_title', 'Admin Added Flat')->sole();
        $this->assertSame('pending', $p->post_status);
        $this->assertSame('₹80 L', $p->formattedPrice());
        $this->assertDatabaseHas('audit_logs', ['action' => 'property.created']);
    }

    public function test_dashboard_lists_listings_with_operations_and_reports_aggregate(): void
    {
        PostProperty::factory()->pending()->create(['property_title' => 'Needs Review Home']);
        PostProperty::factory()->approved()->count(2)->create(['property_type' => 'Apartment / Flat', 'city' => 'Bangalore', 'property_title' => 'Already Approved Home']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Needs Review Home')->assertDontSee('Already Approved Home')
            ->assertSee('Awaiting Your Approval')->assertDontSee('>TYPE</th>', false)
            ->assertViewHas('weeklyMetrics', fn (array $m) => $m['with_ops']['total'] === 1 && $m['awaiting']['total'] === 0)
            ->assertViewHas('propertyTypeLabels', fn ($labels) => $labels->contains('Apartment / Flat'));
        $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertViewHas('cityLabels', fn ($labels) => $labels->contains('Bangalore'));
    }

    public function test_user_properties_page_matches_listings_by_email(): void
    {
        $owner = User::factory()->create(['role' => 'seller', 'name' => 'Owner One', 'email' => 'one@example.test']);
        PostProperty::factory()->approved()->create(['user_email' => 'one@example.test', 'property_title' => 'Owner One Home']);
        PostProperty::factory()->approved()->create(['user_email' => 'two@example.test', 'property_title' => 'Owner Two Home']);

        $this->actingAs($this->admin())->get(route('users.properties', $owner))->assertOk()
            ->assertSee('Properties · Owner One')->assertSee('Owner One Home')->assertDontSee('Owner Two Home');
    }

    public function test_properties_index_is_paginated(): void
    {
        PostProperty::factory()->count(12)->approved()->create();

        $this->actingAs($this->admin())->get(route('properties.index'))->assertOk()
            ->assertViewHas('properties', fn ($p) => $p->perPage() === 10 && $p->total() === 12 && $p->count() === 10);
    }
}
