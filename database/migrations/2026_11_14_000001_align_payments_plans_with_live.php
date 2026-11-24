<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bring the local payments_plans table in line with the live website table:
 *  - button_text (CTA label on the pricing card, e.g. "Upgrade Now")
 *  - active_listings_quota may be NULL for unlimited plans
 * (The live MySQL table already has both; this only touches the local DB.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments_plans', function (Blueprint $t) {
            if (! Schema::hasColumn('payments_plans', 'button_text')) {
                $t->string('button_text')->nullable()->after('sort_order');
            }
            $t->unsignedInteger('active_listings_quota')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments_plans', function (Blueprint $t) {
            $t->dropColumn('button_text');
        });
    }
};
