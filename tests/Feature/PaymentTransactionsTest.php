<?php

namespace Tests\Feature;

use App\Models\PaymentsPlan;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentTransactionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('payment_transactions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('plan_key');
            $t->string('plan_name');
            $t->string('billing_period');
            $t->unsignedInteger('base_amount');
            $t->unsignedInteger('tax_amount');
            $t->unsignedInteger('amount');
            $t->string('currency')->default('INR');
            $t->string('gateway')->default('razorpay');
            $t->string('gateway_order_id')->nullable();
            $t->string('gateway_payment_id')->nullable();
            $t->string('gateway_signature')->nullable();
            $t->string('status')->default('pending');
            $t->string('failure_reason')->nullable();
            $t->text('invoice')->nullable();
            $t->text('invoice_number')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
        PaymentTransaction::flushPlanCache();

        $base = ['plan_type' => 'subscription', 'target_audience' => 'owners_landlords', 'discount' => 0, 'gst_percentage' => 18, 'sort_order' => 0, 'is_active' => true];
        PaymentsPlan::query()->create($base + ['plan_name' => 'Owner Plus', 'slug' => 'owner-plus', 'billing_frequency' => 'monthly', 'price' => 399, 'final_price' => 399]);
        PaymentsPlan::query()->create($base + ['plan_name' => 'Owner Plus', 'slug' => 'owner-plus-2', 'billing_frequency' => 'yearly', 'price' => 2499, 'final_price' => 2499]);

        $tx = fn (array $a) => PaymentTransaction::query()->create($a + ['user_id' => 1, 'plan_key' => 'owner-plus', 'plan_name' => 'Owner Plus']);
        $tx(['billing_period' => 'monthly', 'base_amount' => 39900, 'tax_amount' => 7182, 'amount' => 47082, 'status' => 'paid',
            'gateway_order_id' => 'order_A1', 'gateway_payment_id' => 'pay_A1', 'invoice_number' => 'INV-1', 'paid_at' => now()]);
        $tx(['billing_period' => 'yearly', 'base_amount' => 249900, 'tax_amount' => 44982, 'amount' => 294882, 'status' => 'paid',
            'gateway_order_id' => 'order_B2', 'gateway_payment_id' => 'pay_B2', 'invoice_number' => 'INV-2', 'paid_at' => now()]);
        $tx(['billing_period' => 'yearly', 'base_amount' => 249900, 'tax_amount' => 44982, 'amount' => 294882, 'status' => 'pending', 'gateway_order_id' => 'order_C3']);
    }

    private function admin(): User
    {
        return User::factory()->create(['admin_role' => 'admin']);
    }

    public function test_totals_are_converted_from_paise_and_only_count_paid(): void
    {
        $this->actingAs($this->admin())->get(route('payment-transactions.index'))
            ->assertOk()
            ->assertSee('₹3,419.64')          // 470.82 + 2948.82 paid
            ->assertSee('Owner Plus · Yearly')
            ->assertSee('Owners &amp; Landlords', false)
            ->assertSee('order_C3', false);
    }

    public function test_yearly_payment_matches_the_suffixed_plan_slug(): void
    {
        $yearly = PaymentTransaction::query()->where('billing_period', 'yearly')->first();
        $this->assertSame('owner-plus-2', $yearly->plan()?->slug);
        $this->assertSame(2948.82, $yearly->rupees());
    }

    public function test_filters_and_export(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('payment-transactions.index', ['status' => 'pending']))
            ->assertOk()->assertSee('order_C3', false)->assertDontSee('INV-1');
        $this->actingAs($admin)->get(route('payment-transactions.index', ['search' => 'pay_B2']))
            ->assertOk()->assertSee('INV-2')->assertDontSee('INV-1');

        $csv = $this->actingAs($admin)->get(route('payment-transactions.export', ['status' => 'paid']))->assertOk()->streamedContent();
        $this->assertStringContainsString('INV-1', $csv);
        $this->assertStringNotContainsString('order_C3', $csv);
    }

    public function test_plan_can_be_saved_as_unlimited_with_button_text(): void
    {
        $this->actingAs($this->admin())->post(route('payments-plans.store'), [
            'plan_name' => 'Owner Max', 'plan_type' => 'subscription', 'target_audience' => 'owners_landlords',
            'billing_frequency' => 'monthly', 'price' => 799, 'is_unlimited_listings' => 1, 'button_text' => 'Upgrade Now',
            'gst_percentage' => 18, 'sort_order' => 2, 'is_active' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments_plans', ['plan_name' => 'Owner Max', 'button_text' => 'Upgrade Now', 'active_listings_quota' => null]);
    }

    public function test_page_explains_when_table_is_missing(): void
    {
        Schema::drop('payment_transactions');
        $this->actingAs($this->admin())->get(route('payment-transactions.index'))->assertOk()->assertSee('live:import-dump');
    }
}
