<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | These values will be used by the cloudinary-laravel package.
    | Set CLOUDINARY_URL in your .env file as:
    | cloudinary://API_KEY:API_SECRET@CLOUD_NAME
    |
    */

    'cloud_url' => env('CLOUDINARY_URL'),

    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),

    'api_key' => env('CLOUDINARY_API_KEY'),

    'api_secret' => env('CLOUDINARY_API_SECRET'),

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),

    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),

];
