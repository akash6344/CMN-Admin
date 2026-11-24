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
        Schema::table('post_properties', function (Blueprint $table) {
            if (! Schema::hasColumn('post_properties', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('post_properties', 'state')) {
                $table->string('state')->nullable()->after('location');
            }

            if (! Schema::hasColumn('post_properties', 'images')) {
                $table->json('images')->nullable()->after('count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_properties', function (Blueprint $table) {
            if (Schema::hasColumn('post_properties', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            if (Schema::hasColumn('post_properties', 'state')) {
                $table->dropColumn('state');
            }

            if (Schema::hasColumn('post_properties', 'images')) {
                $table->dropColumn('images');
            }
        });
    }
};
