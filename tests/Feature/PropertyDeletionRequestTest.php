<?php

namespace Tests\Feature;

use App\Models\PostProperty;
use App\Models\PropertyDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

class PropertyDeletionRequestTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    public function test_admin_cannot_create_deletion_requests_from_properties(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $property = PostProperty::factory()->approved()->create(['property_id' => 'P9001']);

        $this->actingAs($admin)
            ->post('/properties/'.$property->id.'/deletion-requests', [
                'reason' => 'Duplicate listing reported by owner.',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('property_deletion_requests', [
            'post_property_id' => $property->id,
        ]);
    }

    public function test_approving_deletion_request_deletes_property(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $owner = User::factory()->create(['role' => 'seller']);
        $property = PostProperty::factory()->approved()->create(['property_id' => 'P9002']);
        $deletionRequest = PropertyDeletionRequest::factory()->pending()->create([
            'post_property_id' => $property->id,
            'property_code' => 'P9002',
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('deletion-requests.approve', $deletionRequest))
            ->assertRedirect();

        $this->assertDatabaseMissing('post_properties', ['id' => $property->id], 'dashboard');
        $this->assertDatabaseHas('property_deletion_requests', [
            'id' => $deletionRequest->id,
            'status' => PropertyDeletionRequest::STATUS_APPROVED,
        ]);
    }

    public function test_rejecting_deletion_request_requires_comment_and_keeps_property(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $owner = User::factory()->create(['role' => 'seller']);
        $property = PostProperty::factory()->approved()->create(['property_id' => 'P9003']);
        $deletionRequest = PropertyDeletionRequest::factory()->pending()->create([
            'post_property_id' => $property->id,
            'property_code' => 'P9003',
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($admin)
            ->from(route('deletion-requests.index'))
            ->patch(route('deletion-requests.reject', $deletionRequest), [])
            ->assertRedirect(route('deletion-requests.index'))
            ->assertSessionHasErrors('review_note');

        $this->actingAs($admin)
            ->patch(route('deletion-requests.reject', $deletionRequest), [
                'review_note' => 'Listing is still valid and should stay live.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('post_properties', ['id' => $property->id], 'dashboard');
        $this->assertDatabaseHas('property_deletion_requests', [
            'id' => $deletionRequest->id,
            'status' => PropertyDeletionRequest::STATUS_REJECTED,
            'review_note' => 'Listing is still valid and should stay live.',
        ]);
    }

    public function test_direct_property_delete_route_is_removed(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $property = PostProperty::factory()->approved()->create();

        $this->actingAs($admin)
            ->delete('/properties/'.$property->id)
            ->assertNotFound();

        $this->assertDatabaseHas('post_properties', ['id' => $property->id], 'dashboard');
    }
}
