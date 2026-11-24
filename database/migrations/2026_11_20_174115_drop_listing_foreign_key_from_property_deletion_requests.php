<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Listings now live in the website database (CMN-Dashboard), so the deletion request keeps the
 * listing id without a foreign key to Admin's old local post_properties table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_deletion_requests', function (Blueprint $table) {
            $table->dropForeign(['post_property_id']);
        });
    }

    public function down(): void
    {
        Schema::table('property_deletion_requests', function (Blueprint $table) {
            $table->foreign('post_property_id')->references('id')->on('post_properties')->nullOnDelete();
        });
    }
};
