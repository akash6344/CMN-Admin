<?php

namespace Tests\Feature;

use App\Models\PaymentsPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_plan_with_the_supplied_payment_plan_columns(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('payments-plans.store'), [
            'plan_name' => 'Broker Elite', 'plan_type' => 'subscription', 'target_audience' => 'agents_brokers',
            'billing_frequency' => 'yearly', 'price' => 1999, 'discount' => 5, 'active_listings_quota' => 3,
            'gst_percentage' => 18, 'sort_order' => 1, 'is_active' => 1, 'features' => "Priority leads\nVerified seller badge",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments_plans', ['plan_name' => 'Broker Elite', 'slug' => 'broker-elite', 'final_price' => 1899.05, 'billing_frequency' => 'yearly']);
    }

    public function test_payments_plan_index_filters_by_audience_and_active_status(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        PaymentsPlan::query()->create(['plan_name' => 'Active Broker', 'slug' => 'active-broker', 'plan_type' => 'subscription', 'target_audience' => 'agents_brokers', 'billing_frequency' => 'monthly', 'price' => 500, 'discount' => 0, 'final_price' => 500, 'active_listings_quota' => 1, 'gst_percentage' => 18, 'sort_order' => 0, 'is_active' => true]);
        PaymentsPlan::query()->create(['plan_name' => 'Inactive Buyer', 'slug' => 'inactive-buyer', 'plan_type' => 'subscription', 'target_audience' => 'buyers_renters', 'billing_frequency' => 'monthly', 'price' => 500, 'discount' => 0, 'final_price' => 500, 'active_listings_quota' => 1, 'gst_percentage' => 18, 'sort_order' => 0, 'is_active' => false]);

        $this->actingAs($admin)->get(route('payments-plans.index', ['audience' => 'agents_brokers', 'status' => 'active']))
            ->assertOk()
            ->assertSee('Active Broker')
            ->assertDontSee('Inactive Buyer');
    }

    public function test_admin_can_disable_an_active_plan(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $plan = PaymentsPlan::query()->create(['plan_name' => 'Example Plan', 'slug' => 'example-plan', 'plan_type' => 'subscription', 'target_audience' => 'buyers_renters', 'billing_frequency' => 'yearly', 'price' => 2999, 'discount' => 10, 'final_price' => 2699.1, 'active_listings_quota' => 3, 'gst_percentage' => 18, 'sort_order' => 0, 'is_active' => true, 'is_featured' => true, 'has_verified_badge' => true]);

        $this->actingAs($admin)->patch(route('payments-plans.toggle', $plan))->assertRedirect();

        $this->assertFalse($plan->fresh()->is_active);
    }
}
