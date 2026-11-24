<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = [
            [
                'title' => 'What is Common Man Housing?',
                'description' => 'An introduction to Common Man Housing and how it helps buyers, renters, and sellers navigate the property market.',
                'status' => 'Active',
            ],
            [
                'title' => 'What\'s the Reason Behind Increasing Rent Rates in Indian Metropolitan Cities?',
                'description' => 'A look at demand, migration, and supply constraints that are pushing rents higher in major Indian cities.',
                'status' => 'Active',
            ],
            [
                'title' => 'Is This the Right Time to Buy a House in Hyderabad?',
                'description' => 'Key market signals for buyers considering a home purchase in Hyderabad right now.',
                'status' => 'Active',
            ],
            [
                'title' => 'Understanding the Concept of Bidding While Renting a House',
                'description' => 'How rental bidding works, when it helps renters, and what landlords should keep in mind.',
                'status' => 'Active',
            ],
        ];

        foreach ($posts as $post) {
            Blog::query()->updateOrCreate(
                ['title' => $post['title']],
                $post,
            );
        }
    }
}
