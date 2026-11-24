<?php

namespace Database\Factories;

use App\Models\PostProperty;
use App\Models\PropertyDeletionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyDeletionRequest>
 */
class PropertyDeletionRequestFactory extends Factory
{
    protected $model = PropertyDeletionRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_property_id' => PostProperty::factory(),
            'property_code' => 'P'.fake()->numerify('####'),
            'property_title' => fake()->sentence(3),
            'requested_by' => User::factory(),
            'reviewed_by' => null,
            'status' => PropertyDeletionRequest::STATUS_PENDING,
            'reason' => fake()->sentence(),
            'review_note' => null,
            'reviewed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => PropertyDeletionRequest::STATUS_PENDING,
            'reviewed_by' => null,
            'review_note' => null,
            'reviewed_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => PropertyDeletionRequest::STATUS_APPROVED,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => PropertyDeletionRequest::STATUS_REJECTED,
            'reviewed_by' => User::factory(),
            'review_note' => 'Not enough justification.',
            'reviewed_at' => now(),
        ]);
    }
}
