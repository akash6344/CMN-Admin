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
        if (Schema::hasTable('post_properties') && ! Schema::hasColumn('post_properties', 'count')) {
            Schema::table('post_properties', function (Blueprint $table) {
                $table->string('count')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('post_properties', 'count')) {
            Schema::table('post_properties', function (Blueprint $table) {
                $table->dropColumn('count');
            });
        }
    }
};
