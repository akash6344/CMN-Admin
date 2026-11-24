<?php

namespace Tests\Feature;

use App\Models\Housing\AppNotification;
use App\Models\Housing\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WebsiteTables;
use Tests\TestCase;

class ComplaintsTest extends TestCase
{
    use RefreshDatabase;
    use WebsiteTables;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['admin_role' => 'admin', 'name' => 'Priya Shah']);
    }

    private function ticket(array $over = []): SupportTicket
    {
        $t = SupportTicket::query()->create($over + [
            'code' => 'T-9001', 'builder_name' => 'Skyline Builders', 'subject' => 'Brochure upload keeps failing', 'category' => 'technical',
            'priority' => 'high', 'status' => 'open', 'raised_by' => 'builder', 'due_at' => now()->addDay(),
        ]);
        $t->messages()->create(['type' => 'description', 'author_type' => 'builder', 'author' => 'Skyline Builders', 'body' => 'The PDF upload stops at 90%.']);

        return $t;
    }

    public function test_lists_real_tickets_with_counts_and_filters(): void
    {
        $this->ticket();
        $this->ticket(['code' => 'T-9002', 'subject' => 'KYC documents rejected', 'category' => 'kyc', 'priority' => 'low', 'status' => 'resolved']);

        $this->actingAs($this->admin)->get('/complaints')->assertOk()
            ->assertSee('Brochure upload keeps failing')->assertSee('The PDF upload stops at 90%.')->assertSee('Skyline Builders')
            ->assertSee('KYC documents rejected')->assertSee('Open (1)')->assertSee('Resolved (1)')->assertSee('1 Open')
            ->assertDontSee('Fake Property Listing');

        $this->actingAs($this->admin)->get('/complaints?status=resolved')->assertSee('KYC documents rejected')->assertDontSee('Brochure upload keeps failing');
        $this->actingAs($this->admin)->get('/complaints?q=brochure')->assertSee('Brochure upload keeps failing')->assertDontSee('KYC documents rejected');
    }

    public function test_reply_reaches_the_builder_and_moves_ticket_to_in_progress(): void
    {
        $t = $this->ticket();

        $this->actingAs($this->admin)->post("/complaints/{$t->id}/reply", ['type' => 'reply', 'body' => 'We fixed the upload limit — please retry.'])
            ->assertRedirect(route('complaints.show', $t).'#thread');

        $t->refresh();
        $this->assertSame('in_progress', $t->status);
        $this->assertNotNull($t->first_response_at);
        $this->assertDatabaseHas('support_ticket_messages', ['support_ticket_id' => $t->id, 'type' => 'reply', 'author_type' => 'brm', 'author' => 'Priya Shah · Admin'], 'housing');
        $this->assertTrue(AppNotification::query()->where('audience', 'builder')->where('company', 'Skyline Builders')->where('type', 'ticket_reply')->exists());
        $this->actingAs($this->admin)->get("/complaints/{$t->id}")->assertOk()->assertSee('We fixed the upload limit');
    }

    public function test_internal_note_does_not_notify_builder(): void
    {
        $t = $this->ticket();

        $this->actingAs($this->admin)->post("/complaints/{$t->id}/reply", ['type' => 'note', 'body' => 'Check with tech team first.']);

        $this->assertSame('open', $t->fresh()->status);
        $this->assertFalse(AppNotification::query()->where('audience', 'builder')->exists());
        $this->actingAs($this->admin)->get("/complaints/{$t->id}")->assertSee('Internal note');
    }

    public function test_take_action_resolves_escalates_and_reopens(): void
    {
        $t = $this->ticket();

        $this->actingAs($this->admin)->patch("/complaints/{$t->id}", ['status' => 'escalated', 'priority' => 'critical', 'note' => 'Builder threatened to leave.']);
        $t->refresh();
        $this->assertSame('escalated', $t->status);
        $this->assertSame('critical', $t->priority);
        $this->assertTrue(AppNotification::query()->where('audience', 'brm')->where('type', 'ticket_escalated')->exists());

        $this->actingAs($this->admin)->patch("/complaints/{$t->id}", ['status' => 'resolved', 'priority' => 'critical'])->assertSessionHasNoErrors();
        $this->assertNotNull($t->fresh()->resolved_at);
        $this->assertTrue(AppNotification::query()->where('type', 'ticket_resolved')->exists());

        $this->actingAs($this->admin)->patch("/complaints/{$t->id}", ['status' => 'open', 'priority' => 'critical']);
        $this->assertNull($t->fresh()->resolved_at);
        $this->assertSame('open', $t->fresh()->status);
    }

    public function test_validation_and_unknown_ticket(): void
    {
        $t = $this->ticket();
        $this->get('/complaints')->assertRedirect(route('login'));
        $this->actingAs($this->admin)->post("/complaints/{$t->id}/reply", ['type' => 'reply', 'body' => ''])->assertSessionHasErrors('body');
        $this->actingAs($this->admin)->get('/complaints/999')->assertNotFound();
    }
}
