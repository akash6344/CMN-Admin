<?php

namespace Database\Factories;

use App\Models\PostProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A website listing (CMN-Dashboard post_properties).
 *
 * @extends Factory<PostProperty>
 */
class PostPropertyFactory extends Factory
{
    protected $model = PostProperty::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 500),
            'user_name' => fake()->name(),
            'user_email' => fake()->safeEmail(),
            'user_mobile' => '9'.fake()->numerify('#########'),
            'user_role' => 'owner',
            'posted_as' => 'Owner',
            'property_id' => 'P'.fake()->unique()->numerify('####'),
            'property_type' => fake()->randomElement(['Apartment / Flat', 'Independent House / Villa', 'Residential Plot', 'Commercial Space', 'PG / Co-living']),
            'property_for' => 'Sale',
            'property_title' => fake()->sentence(3),
            'city' => fake()->randomElement(['Bangalore', 'Hyderabad', 'Mumbai', 'Chennai', 'Pune']),
            'location' => fake()->streetName(),
            'price' => (string) fake()->numberBetween(25, 250),
            'price_text' => 'Lac',
            'post_status' => 'pending',
            'show_on_website' => 'no',
            'count' => fake()->numberBetween(0, 250),
            'bed' => (string) fake()->numberBetween(1, 4),
            'bath' => (string) fake()->numberBetween(1, 4),
            'balcony' => (string) fake()->numberBetween(0, 3),
            'built_area' => (string) fake()->numberBetween(600, 3500),
            'built_text' => 'sq.ft',
            'furnishing' => fake()->randomElement(['Furnished', 'Semi-Furnished', 'Unfurnished']),
            'about_property' => fake()->paragraph(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['post_status' => 'pending', 'show_on_website' => 'no']);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['post_status' => 'Approved', 'show_on_website' => 'Show']);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => ['post_status' => 'Rejected', 'show_on_website' => 'no', 'rejection_reason' => fake()->sentence()]);
    }
}
