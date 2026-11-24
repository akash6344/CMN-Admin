<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 32)->nullable()->after('email');
            $table->string('city')->nullable()->after('mobile');
            $table->string('location')->nullable()->after('city');
            $table->string('state')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'city', 'location', 'state']);
        });
    }
};
