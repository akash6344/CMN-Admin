<?php

namespace Tests\Feature;

use App\Models\Agent\AgentCommission;
use App\Models\Agent\AgentPayout;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AgentEarningsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $agent = Schema::connection('agent');
        $agent->create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->timestamps();
        });
        $agent->create('payouts', function ($t) {
            $t->id();
            $t->string('payout_code');
            $t->unsignedBigInteger('agent_id');
            $t->unsignedBigInteger('amount');
            $t->string('status')->default('requested');
            $t->string('bank_reference')->nullable();
            $t->timestamp('requested_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
        $agent->create('commissions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('agent_id');
            $t->unsignedBigInteger('bargain_id')->nullable();
            $t->unsignedBigInteger('payout_id')->nullable();
            $t->string('deal_code');
            $t->string('property_title');
            $t->string('property_location')->nullable();
            $t->string('property_image')->nullable();
            $t->unsignedBigInteger('post_property_id')->nullable();
            $t->string('client_name');
            $t->string('deal_type')->default('sale');
            $t->string('entry_type')->default('commission');
            $t->unsignedBigInteger('deal_value')->nullable();
            $t->bigInteger('amount');
            $t->string('status')->default('pending');
            $t->timestamp('earned_at');
            $t->timestamp('received_at')->nullable();
            $t->timestamps();
        });
        DB::connection('agent')->table('users')->insert(['id' => 1, 'name' => 'Rahul Agarwal', 'email' => 'rahul@test.com']);
        $this->actingAs(User::factory()->create(['admin_role' => 'admin']));
    }

    public function test_admin_records_commission_and_marks_it_received(): void
    {
        $this->post(route('agent-earnings.commissions.store'), [
            'agent_id' => 1, 'entry_type' => 'commission', 'deal_type' => 'sale', 'deal_code' => 'D100',
            'property_title' => 'Palm Heights 4BHK', 'client_name' => 'Ravi Kumar', 'deal_value' => 12500000,
            'amount' => 25000, 'status' => 'pending', 'earned_at' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $c = AgentCommission::query()->firstOrFail();
        $this->assertSame('pending', $c->status);
        $this->get(route('agent-earnings.index'))->assertOk()->assertSee('Palm Heights 4BHK')->assertSee('Rahul Agarwal')->assertSee('₹25,000');

        $this->patch(route('agent-earnings.commissions.received', $c->id))->assertRedirect();
        $this->assertSame('received', $c->fresh()->status);
        $this->assertNotNull($c->fresh()->received_at);
        $this->assertTrue(AuditLog::query()->where('action', 'agent_commission.received')->exists());
    }

    public function test_payout_moves_to_paid_and_links_commissions(): void
    {
        AgentCommission::query()->create(['agent_id' => 1, 'deal_code' => 'D1', 'property_title' => 'X', 'client_name' => 'Y', 'amount' => 15000,
            'status' => 'received', 'earned_at' => now()->subDays(3), 'received_at' => now()->subDay()]);
        $p = AgentPayout::query()->create(['payout_code' => 'PO-0001', 'agent_id' => 1, 'amount' => 15000, 'status' => 'requested', 'requested_at' => now()]);

        $this->get(route('agent-earnings.index', ['tab' => 'payouts']))->assertOk()->assertSee('PO-0001')->assertSee('Mark Paid');
        $this->patch(route('agent-earnings.payouts.process', $p->id))->assertRedirect();
        $this->assertSame('processing', $p->fresh()->status);

        $this->patch(route('agent-earnings.payouts.paid', $p->id), [])->assertSessionHasErrors('bank_reference');
        $this->patch(route('agent-earnings.payouts.paid', $p->id), ['bank_reference' => 'UTR123'])->assertRedirect();
        $this->assertSame('paid', $p->fresh()->status);
        $this->assertSame('UTR123', $p->fresh()->bank_reference);
        $this->assertSame($p->id, (int) AgentCommission::query()->first()->payout_id);
    }

    public function test_rejected_payout_cannot_be_paid(): void
    {
        $p = AgentPayout::query()->create(['payout_code' => 'PO-0002', 'agent_id' => 1, 'amount' => 5000, 'status' => 'requested', 'requested_at' => now()]);
        $this->patch(route('agent-earnings.payouts.reject', $p->id))->assertRedirect();
        $this->assertSame('rejected', $p->fresh()->status);
        $this->patch(route('agent-earnings.payouts.paid', $p->id), ['bank_reference' => 'UTR9'])->assertSessionHasErrors('payout');
    }

    public function test_page_explains_when_agent_db_missing(): void
    {
        Schema::connection('agent')->drop('commissions');
        $this->get(route('agent-earnings.index'))->assertOk()->assertSee("Agent database isn't reachable", false);
    }
}
