<?php

namespace Tests\Feature;

use App\Models\Dashboard\Listing;
use App\Models\Dashboard\PropertyReview;
use App\Models\PostProperty;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

/**
 * The live website DB (cmn_laravel_13): form_status + empty post_status, Pause / Deleted, rejection_comment,
 * documents.property_id / document_name, Laravel database notifications.
 */
class LiveWebsiteSchemaTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $d = Schema::connection('dashboard');
        $d->table('post_properties', fn (Blueprint $t) => [$t->string('form_status')->nullable(), $t->text('rejection_comment')->nullable()]);
        $d->drop('documents');
        $d->create('documents', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id');
            $t->integer('property_id')->nullable();
            $t->string('document_name');
            $t->string('verification_status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        $d->drop('notifications');
        $d->create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        // Eloquent caches a guarded model's columns per class for the whole process — forget the narrower local shape
        foreach ([PostProperty::class, Listing::class] as $class) {
            \Closure::bind(fn () => static::$guardableColumns = [], null, $class)();
        }
        $this->admin = User::factory()->create(['admin_role' => 'admin', 'name' => 'Priya Admin']);
    }

    private function listing(string $title, string $post, string $form): int
    {
        return (int) DB::connection('dashboard')->table('post_properties')->insertGetId([
            'user_id' => 7, 'user_name' => 'Ramesh', 'user_role' => 'buyer', 'property_title' => $title, 'post_status' => $post, 'form_status' => $form,
            'show_on_website' => 'Show', 'rejection_reason' => 'Old reason', 'rejection_comment' => 'Old comment', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_tabs_use_the_live_statuses(): void
    {
        $this->listing('Waiting Flat', '', 'Published');
        $this->listing('Half Filled Draft', '', 'draft');
        $this->listing('Paused Villa', 'Pause', 'Published');
        $this->listing('Deleted Plot', 'Deleted', 'Published');

        $this->actingAs($this->admin)->get('/properties?status=with_ops')->assertOk()
            ->assertSee('Waiting Flat')->assertDontSee('Paused Villa')->assertDontSee('Deleted Plot')->assertDontSee('Half Filled Draft');
        $this->actingAs($this->admin)->get('/properties')->assertOk()
            ->assertSee('Paused Villa')->assertSee('Paused')->assertSee('Deleted Plot')->assertDontSee('Half Filled Draft');
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertDontSee('Paused Villa')->assertDontSee('Half Filled Draft');
    }

    public function test_approve_clears_both_rejection_columns_and_notifies_the_owner_in_the_live_format(): void
    {
        $id = $this->listing('Waiting Flat', '', 'Published');
        DB::connection('dashboard')->table('documents')->insert(['user_id' => 7, 'property_id' => $id, 'document_name' => 'AGREEMENT Letter', 'verification_status' => 'pending']);
        $r = PropertyReview::query()->create(['kind' => 'listing', 'post_property_id' => $id, 'status' => 'ready_for_admin', 'submitted_to_admin_at' => now()]);

        $this->actingAs($this->admin)->get(route('properties.show', 'listing-'.$id))->assertOk()->assertSee('AGREEMENT Letter');
        $this->actingAs($this->admin)->patch(route('properties.reviews.approve', $r))->assertSessionHasNoErrors();

        $row = DB::connection('dashboard')->table('post_properties')->find($id);
        $this->assertSame(['Approved', null, null], [$row->post_status, $row->rejection_reason, $row->rejection_comment]);
        $n = DB::connection('dashboard')->table('notifications')->sole();
        $this->assertSame([7, 'App\Models\User'], [(int) $n->notifiable_id, $n->notifiable_type]);
        $data = json_decode($n->data, true);
        $this->assertSame(['property', 'property.approved', 'success'], [$data['category'], $data['type'], $data['tone']]);
    }

    public function test_a_listing_deleted_after_handover_cannot_be_approved(): void
    {
        $id = $this->listing('Gone Flat', 'Deleted', 'Published');
        $r = PropertyReview::query()->create(['kind' => 'listing', 'post_property_id' => $id, 'status' => 'ready_for_admin', 'submitted_to_admin_at' => now()]);

        $this->actingAs($this->admin)->patch(route('properties.reviews.approve', $r))->assertSessionHasErrors('review');
        $this->assertSame('ready_for_admin', $r->fresh()->status);
    }
}
