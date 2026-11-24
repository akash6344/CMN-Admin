<?php

namespace Tests\Feature;

use App\Models\Dashboard\PropertyReview;
use App\Models\PostProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

/** The top bar on every Admin page: search, notifications, profile menu. */
class TopBarTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    public function test_every_page_gets_the_shared_menu_script(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        foreach (['properties.index', 'deletion-requests.index', 'audit-logs.index', 'payment-transactions.index', 'locations.index', 'blogs.index', 'search'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk()
                ->assertSee('id="profileDropdownTrigger"', false)->assertSee("['profileDropdownTrigger', 'profileDropdownMenu'", false);
        }
    }

    public function test_search_finds_properties_and_users(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        User::factory()->create(['name' => 'Kiran Banjara', 'email' => 'kiran@example.test']);
        PostProperty::factory()->create(['property_title' => 'Banjara Hills Villa', 'city' => 'Hyderabad']);

        $this->actingAs($admin)->get(route('search', ['q' => 'banjara']))->assertOk()
            ->assertSee('Banjara Hills Villa')->assertSee('Kiran Banjara')->assertSee(route('properties.index', ['q' => 'banjara']), false);
        $this->actingAs($admin)->get(route('search', ['q' => 'zzz-none']))->assertSee('No properties match.')->assertSee('No users match.');
    }

    public function test_bell_lists_what_needs_attention(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $this->actingAs($admin)->get(route('dashboard'))->assertSee('You’re all caught up.')->assertDontSee('notification-badge-dot', false);

        $p = PostProperty::factory()->create();
        PropertyReview::query()->create(['kind' => 'listing', 'post_property_id' => $p->id, 'status' => 'ready_for_admin', 'submitted_to_admin_at' => now()]);
        $this->actingAs($admin)->get(route('dashboard'))->assertSee('1 property waiting for your approval')->assertSee('notification-badge-dot', false)
            ->assertSee(route('properties.index', ['status' => 'pending']), false);
    }
}
