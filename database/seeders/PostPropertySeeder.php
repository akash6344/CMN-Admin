<?php

namespace Database\Seeders;

use App\Models\PostProperty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PostPropertySeeder extends Seeder
{
    public function run(): void
    {
        $owners = collect([
            ['name' => 'Rahul Sharma', 'email' => 'rahul.owner@cmnhousing.test', 'mobile' => '9000000001', 'city' => 'Bangalore', 'state' => 'Karnataka', 'role' => 'seller'],
            ['name' => 'Priya Nair', 'email' => 'priya.owner@cmnhousing.test', 'mobile' => '9000000002', 'city' => 'Hyderabad', 'state' => 'Telangana', 'role' => 'agent'],
            ['name' => 'Amit Patel', 'email' => 'amit.owner@cmnhousing.test', 'mobile' => '9000000003', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'role' => 'seller'],
        ])->map(function (array $data): User {
            return User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'mobile' => $data['mobile'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'location' => $data['city'],
                    'role' => $data['role'],
                    'password' => Hash::make('password'),
                ]
            );
        });

        $listings = [
            [
                'property_id' => 'P1001',
                'owner' => $owners[0],
                'property_type' => 'Apartment / Flat',
                'property_title' => '3BHK Apartment in Indiranagar',
                'city' => 'Bangalore',
                'location' => 'Indiranagar',
                'state' => 'Karnataka',
                'price' => '8500000',
                'post_status' => 'Pending',
                'form_status' => 'Published',
                'show_on_website' => 'Hide',
                'images' => ['136f951362dab62e64eb8e841183c2a9-20261008_100826.jpg', 'images/2596a54cdbb555cfd09cd5d991da0f55-20261008_100826.jpg'],
                'specs' => [
                    'bed' => '3', 'bath' => '3', 'balcony' => '2', 'available_from' => 'October 22, 2026',
                    'furnishing' => 'Semi-Furnished', 'built_area' => '1450', 'built_unit' => 'sq.ft',
                    'total_floors' => '5', 'floor_no' => '3', 'car_parking' => '1', 'bike_parking' => '1',
                    'property_age' => '2 Years', 'landmark' => 'Near Metro Station', 'listed_price' => '85,00,000',
                    'price_negotiable' => 'Yes', 'price_true_value' => '8700000', 'new_min_price' => '8200000',
                    'new_max_price' => '8600000', 'enable_smart_bargain' => 'YES',
                    'about_property' => 'Spacious 3BHK flat in Indiranagar with modular kitchen, 2 balconies, and basement parking.',
                ],
            ],
            [
                'property_id' => 'P1002',
                'owner' => $owners[1],
                'property_type' => 'Independent House / Villa',
                'property_title' => '4BHK Villa near Gachibowli',
                'city' => 'Hyderabad',
                'location' => 'Gachibowli',
                'state' => 'Telangana',
                'price' => '18500000',
                'post_status' => 'Approved',
                'form_status' => 'Published',
                'show_on_website' => 'Show',
                'images' => ['137ffea9336f8b47a66439fc34e981ee-20261007_081247.jpg', 'images/1457c0d6bfcb4967418bfb8ac142f64a-20261007_081351.jpg'],
                'specs' => [
                    'bed' => '4', 'bath' => '4', 'balcony' => '3', 'furnishing' => 'Furnished',
                    'built_area' => '3200', 'built_unit' => 'sq.ft', 'total_floors' => '2', 'floor_no' => 'G+1',
                    'car_parking' => '2', 'bike_parking' => '2', 'property_age' => '1 Year',
                    'landmark' => 'Near Financial District', 'listed_price' => '1,85,00,000',
                    'price_negotiable' => 'Yes', 'enable_smart_bargain' => 'YES',
                    'about_property' => 'Premium villa with private lawn, servant room, and covered parking.',
                ],
            ],
            [
                'property_id' => 'P1003',
                'owner' => $owners[2],
                'property_type' => 'Commercial Space',
                'property_title' => 'Office Space in Andheri East',
                'city' => 'Mumbai',
                'location' => 'Andheri East',
                'state' => 'Maharashtra',
                'price' => '22000000',
                'post_status' => 'Approved',
                'form_status' => 'Published',
                'show_on_website' => 'Show',
                'images' => ['b6a8cdd64382383f29fac327a8292096-20261008_101805.jpg', 'images/486c0401c56bf7ec2daa9eba58907da9-20261008_101805.jpg'],
                'specs' => [
                    'built_area' => '1800', 'built_unit' => 'sq.ft', 'total_floors' => '12', 'floor_no' => '7',
                    'car_parking' => '4', 'e_charges' => 'Included', 'w_charges' => 'Included',
                    'landmark' => 'Near Metro', 'listed_price' => '2,20,00,000', 'price_negotiable' => 'No',
                    'm_charges' => '25000', 'booking_amount' => '500000', 'enable_smart_bargain' => 'NO',
                    'about_property' => 'Ready-to-move commercial office with lift access and backup power.',
                ],
            ],
            [
                'property_id' => 'P1004',
                'owner' => $owners[0],
                'property_type' => 'Residential Plot',
                'property_title' => 'HMDA Approved Plot for Sale',
                'city' => 'Hyderabad',
                'location' => 'Shadnagar',
                'state' => 'Telangana',
                'price' => '9500000',
                'post_status' => 'Rejected',
                'form_status' => 'Published',
                'show_on_website' => 'Hide',
                'images' => ['c5f7756d9f92a8954884ec415f79d120-20261009_100409.jpg'],
                'specs' => [
                    'available_from' => 'October 22, 2026', 'built_area' => '250', 'built_unit' => 'sq.yards',
                    'landmark' => 'Near Metro Station', 'listed_price' => '95,00,000', 'price_negotiable' => 'Yes',
                    'price_true_value' => '10412000', 'new_min_price' => '8740000', 'new_max_price' => '9272000',
                    'enable_smart_bargain' => 'YES',
                    'about_property' => "Clear-title HMDA & TG-RERA approved residential plot with blacktop roads, drainage, and water supply.\n\n• Approval: HMDA & TG-RERA Approved Layout\n• Title: 100% Clear Title & Spot Registration",
                ],
            ],
            [
                'property_id' => 'P1005',
                'owner' => $owners[1],
                'property_type' => 'PG / Co-living',
                'property_title' => 'Premium PG near Hitech City',
                'city' => 'Hyderabad',
                'location' => 'Hitech City',
                'state' => 'Telangana',
                'price' => '18000',
                'post_status' => 'Pending',
                'form_status' => 'Draft',
                'show_on_website' => 'Hide',
                'images' => ['e2c0be24560d78c5e599c2a9c9d0bbd2-20261007_093243.jpg', 'images/007d4a1214289aea09b9759ae1324e96-20261007_093243.jpg'],
                'specs' => [
                    'bed' => '1', 'bath' => '1', 'furnishing' => 'Fully Furnished', 'available_for' => 'Boys/Girls',
                    'rent_ag_duration' => '11 Months', 'available_from' => 'November 1, 2026',
                    'car_parking' => 'Shared', 'bike_parking' => 'Yes', 'm_charges' => '2000',
                    'landmark' => 'Near Hitech City', 'enable_smart_bargain' => 'NO',
                    'about_property' => 'Premium PG with Wi-Fi, meals, and housekeeping.',
                ],
            ],
            [
                'property_id' => 'P1006',
                'owner' => $owners[2],
                'property_type' => 'Apartment / Flat',
                'property_title' => '2BHK Flat in Powai',
                'city' => 'Mumbai',
                'location' => 'Powai',
                'state' => 'Maharashtra',
                'price' => '12500000',
                'post_status' => 'Approved',
                'form_status' => 'Published',
                'show_on_website' => 'Show',
                'images' => ['images/892c91e0a653ba19df81a90f89d99bcd-20261008_101805.jpg'],
                'specs' => [
                    'bed' => '2', 'bath' => '2', 'balcony' => '1', 'furnishing' => 'Unfurnished',
                    'built_area' => '980', 'built_unit' => 'sq.ft', 'total_floors' => '18', 'floor_no' => '11',
                    'car_parking' => '1', 'property_age' => '5 Years', 'listed_price' => '1,25,00,000',
                    'price_negotiable' => 'Yes', 'enable_smart_bargain' => 'YES',
                    'about_property' => 'Lake-facing 2BHK with clubhouse access.',
                ],
            ],
        ];

        foreach ($listings as $listing) {
            /** @var User $owner */
            $owner = $listing['owner'];

            PostProperty::query()->updateOrCreate(
                ['property_id' => $listing['property_id']],
                [
                    'user_id' => $owner->id,
                    'user_name' => $owner->name,
                    'property_type' => $listing['property_type'],
                    'property_title' => $listing['property_title'],
                    'city' => $listing['city'],
                    'location' => $listing['location'],
                    'state' => $listing['state'],
                    'price' => $listing['price'],
                    'post_status' => $listing['post_status'],
                    'form_status' => $listing['form_status'],
                    'show_on_website' => $listing['show_on_website'],
                    'count' => fake()->numberBetween(12, 320),
                    'images' => $listing['images'],
                    'about_property' => $listing['specs']['about_property'] ?? null,
                    ...collect($listing['specs'] ?? [])->except('about_property')->all(),
                ]
            );
        }
    }
}
