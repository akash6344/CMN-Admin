<?php

namespace Database\Factories;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Blog>
 */
class BlogFactory extends Factory
{
    protected $model = Blog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'image_path' => null,
            'description' => fake()->paragraphs(3, true),
            'status' => 'Active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => 'Inactive',
        ]);
    }
}
