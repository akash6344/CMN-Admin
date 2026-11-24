<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // the public website (CMN-Dashboard): where listing photos are served from
    'website' => [
        'property_image_base' => env('PROPERTY_IMAGE_BASE'),
        // the listing's page, linked from website notifications ({id} = post_properties.id, {property_id} = its CMN code); empty = no link
        'listing_url' => env('WEBSITE_LISTING_URL', ''),
    ],

    // Builder portal (CMN-housing) — support-ticket attachments are served from its public /storage.
    'housing' => [
        'public_url' => env('HOUSING_PUBLIC_URL', 'http://localhost:8091'),
    ],

    // BRM portal (CMN-Builder-Relationship) — reviews builder projects; Admin links to its Project Approvals page.
    'brm' => [
        'url' => env('BRM_URL', 'http://localhost:8090'),
    ],

];
