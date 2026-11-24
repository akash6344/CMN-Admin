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
        Schema::create('post_properties', function (Blueprint $table) {
            $table->id();
            $table->string('property_id')->nullable()->unique();
            $table->string('user_name')->nullable();
            $table->string('property_type')->nullable();
            $table->string('property_title')->nullable();
            $table->string('city')->nullable();
            $table->string('location')->nullable();
            $table->string('price')->nullable();
            $table->string('post_status')->nullable();
            $table->string('form_status')->nullable();
            $table->string('show_on_website')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['post_status', 'form_status', 'show_on_website']);
            $table->index('created_at');
            $table->index('property_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_properties');
    }
};
