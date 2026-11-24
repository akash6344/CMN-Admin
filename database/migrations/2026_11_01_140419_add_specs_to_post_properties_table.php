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
            $columns = [
                'bed' => 'string',
                'bath' => 'string',
                'balcony' => 'string',
                'available_from' => 'string',
                'available_for' => 'string',
                'furnishing' => 'string',
                'built_area' => 'string',
                'built_unit' => 'string',
                'total_floors' => 'string',
                'floor_no' => 'string',
                'e_charges' => 'string',
                'w_charges' => 'string',
                'car_parking' => 'string',
                'bike_parking' => 'string',
                'property_age' => 'string',
                'rent_ag_duration' => 'string',
                'about_property' => 'text',
                'landmark' => 'string',
                'listed_price' => 'string',
                'price_negotiable' => 'string',
                'm_charges' => 'string',
                'booking_amount' => 'string',
                'price_true_value' => 'string',
                'new_min_price' => 'string',
                'new_max_price' => 'string',
                'enable_smart_bargain' => 'string',
            ];

            foreach ($columns as $name => $type) {
                if (Schema::hasColumn('post_properties', $name)) {
                    continue;
                }

                if ($type === 'text') {
                    $table->text($name)->nullable()->after('images');
                } else {
                    $table->string($name)->nullable()->after('images');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_properties', function (Blueprint $table) {
            $columns = [
                'bed', 'bath', 'balcony', 'available_from', 'available_for', 'furnishing',
                'built_area', 'built_unit', 'total_floors', 'floor_no', 'e_charges', 'w_charges',
                'car_parking', 'bike_parking', 'property_age', 'rent_ag_duration', 'about_property',
                'landmark', 'listed_price', 'price_negotiable', 'm_charges', 'booking_amount',
                'price_true_value', 'new_min_price', 'new_max_price', 'enable_smart_bargain',
            ];

            $existing = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('post_properties', $column)));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
