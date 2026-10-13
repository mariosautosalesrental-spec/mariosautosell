<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cars per page
    |--------------------------------------------------------------------------
    |
    | How many cars the public lot shows at once; "Show more cars" adds this
    | many again.
    |
    */

    'cars_per_page' => (int) env('CARS_PER_PAGE', 12),

    /*
    |--------------------------------------------------------------------------
    | Store timezone
    |--------------------------------------------------------------------------
    |
    | Used for "today" in the admin and to close the month on the dashboard.
    | Timestamps stay in UTC in the database.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'America/New_York'),

    /*
    |--------------------------------------------------------------------------
    | Car photos
    |--------------------------------------------------------------------------
    |
    | Filesystem disk that holds the car photos and their conversions.
    | "public" locally, "s3" (Cloudflare R2 or S3) in production.
    |
    */

    'photos_disk' => env('PHOTOS_DISK', 'public'),

    'photo_sizes' => [
        'thumb' => 600,
        'large' => 1600,
    ],

    'max_photo_kb' => 10240,

];
