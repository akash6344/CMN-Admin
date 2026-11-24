<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentsPlan extends Model
{
    protected $fillable = ['plan_name', 'slug', 'plan_type', 'description', 'target_audience', 'billing_frequency', 'price', 'discount', 'final_price', 'currency', 'active_listings_quota', 'is_unlimited_listings', 'gst_percentage', 'gst_sac_code', 'gst_description', 'features', 'team_seats', 'boosted_listings_quota', 'has_ai_features', 'has_priority_leads', 'has_verified_badge', 'is_active', 'is_featured', 'sort_order', 'button_text'];

    /** Live website DB in production; this app's DB in tests (see config/payments.php). */
    public function getConnectionName(): ?string
    {
        return config('payments.connection');
    }

    public const AUDIENCES = [
        'buyers_renters' => 'Buyers & Renters',
        'agents_brokers' => 'Agents & Brokers',
        'owners_landlords' => 'Owners & Landlords',
        'builders_developers' => 'Builders & Developers',
    ];

    public function audienceLabel(): string
    {
        return self::AUDIENCES[$this->target_audience] ?? 'All customers';
    }

    protected function casts(): array
    {
        return ['features' => 'array', 'price' => 'decimal:2', 'discount' => 'decimal:2', 'final_price' => 'decimal:2', 'is_unlimited_listings' => 'boolean', 'has_ai_features' => 'boolean', 'has_priority_leads' => 'boolean', 'has_verified_badge' => 'boolean', 'is_active' => 'boolean', 'is_featured' => 'boolean'];
    }
}
