<?php
// File location: backend/config/cloudinary.php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Cloudinary cloud storage service
    |
    */

    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    
    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    */
    
    'folder' => env('CLOUDINARY_FOLDER', 'shuttleplay/products'),
    'secure' => true,
    'overwrite' => false,
    'unique_filename' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Image Transformation Settings
    |--------------------------------------------------------------------------
    */
    
    'transformations' => [
        'thumbnail' => [
            'width' => 300,
            'height' => 300,
            'crop' => 'fill',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ],
        'medium' => [
            'width' => 600,
            'height' => 600,
            'crop' => 'fill',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ],
        'large' => [
            'width' => 1200,
            'height' => 1200,
            'crop' => 'fill',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Upload Validation
    |--------------------------------------------------------------------------
    */
    
    'allowed_formats' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    'max_file_size' => 10485760, // 10MB in bytes
    'max_files_per_product' => 10,
];