<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name', 150);
            $table->string('slug', 180)->unique();
            $table->string('plan_type');
            $table->text('description')->nullable();
            $table->string('target_audience')->nullable();
            $table->string('billing_frequency');
            $table->decimal('price', 12, 2);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('final_price', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->unsignedInteger('active_listings_quota')->default(0);
            $table->boolean('is_unlimited_listings')->default(false);
            $table->decimal('gst_percentage', 5, 2)->default(18);
            $table->string('gst_sac_code', 20)->nullable();
            $table->string('gst_description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('team_seats')->nullable();
            $table->unsignedInteger('boosted_listings_quota')->nullable();
            $table->boolean('has_ai_features')->default(false);
            $table->boolean('has_priority_leads')->default(false);
            $table->boolean('has_verified_badge')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['target_audience', 'billing_frequency', 'is_active']);
            $table->index(['sort_order', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments_plans');
    }
};
