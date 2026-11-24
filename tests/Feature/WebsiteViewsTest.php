<?php

namespace Tests\Feature;

use App\Models\PostProperty;
use App\Models\User;
use App\Support\WebsiteViews;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

/** Listing Views use the website's real `property_views` log, not the legacy post_properties.count counter. */
class WebsiteViewsTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    private function admin(): User
    {
        return User::factory()->create(['admin_role' => 'admin']);
    }

    /** The live website's columns (cmn_laravel_13). */
    private function createWebsiteViewsTable(): void
    {
        Schema::connection('dashboard')->create('property_views', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('property_id')->nullable();
            $t->unsignedBigInteger('seller_id')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('viewed_at')->nullable();
            $t->timestamps();
        });
    }

    private function logView(int $property, $at, string $ip = '127.0.0.1'): void
    {
        DB::connection('dashboard')->table('property_views')
            ->insert(['property_id' => $property, 'seller_id' => 1, 'ip_address' => $ip, 'viewed_at' => $at, 'created_at' => $at, 'updated_at' => $at]);
    }

    public function test_dashboard_card_and_listing_page_count_real_website_views(): void
    {
        $this->createWebsiteViewsTable();
        $flat = PostProperty::factory()->approved()->create(['property_title' => 'Viewed Flat', 'count' => 4321]);
        $other = PostProperty::factory()->approved()->create(['property_title' => 'Other Flat']);
        $this->logView($flat->id, now());
        $this->logView($flat->id, now()->subDays(10));
        $this->logView($flat->id, now()->subDays(45));
        $this->logView($other->id, now());

        $this->assertSame(4, WebsiteViews::count());
        $this->assertSame(3, WebsiteViews::count([$flat->id]));
        $this->assertSame(1, WebsiteViews::uniqueViewers());

        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk()
            ->assertSeeInOrder(['Listing Views', '4'])->assertDontSee('4,321');

        $this->actingAs($this->admin())->get(route('properties.show', 'listing-'.$flat->id))->assertOk()
            ->assertSeeInOrder(['<dt>Views</dt><dd>3</dd>', '<dt>Views (last 30 days)</dt><dd>2</dd>'], false)
            ->assertDontSee('4,321');
    }

    public function test_pages_work_when_the_website_views_table_is_missing(): void
    {
        $flat = PostProperty::factory()->approved()->create(['property_title' => 'Unviewed Flat', 'count' => 4321]);

        $this->assertFalse(WebsiteViews::available());
        $this->assertSame(0, WebsiteViews::count());
        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk()->assertSee('Listing Views')->assertDontSee('4,321');
        $this->actingAs($this->admin())->get(route('properties.show', 'listing-'.$flat->id))->assertOk()
            ->assertSee('<dt>Views</dt><dd>—</dd>', false);
    }
}
