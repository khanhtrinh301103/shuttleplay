<?php
// File: backend/test-cloudinary-connection.php
// Chạy: php test-cloudinary-connection.php

require_once 'vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Admin\AdminApi;

try {
    echo "🔄 Testing Cloudinary Connection...\n\n";

    // Configure Cloudinary with SSL disabled
    $cloudinaryConfig = [
        'cloud' => [
            'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
            'api_key' => $_ENV['CLOUDINARY_API_KEY'],
            'api_secret' => $_ENV['CLOUDINARY_API_SECRET'],
        ],
        'url' => [
            'secure' => true
        ]
    ];

    // Disable SSL verification for local testing
    if (($_ENV['APP_ENV'] ?? 'local') === 'local' || ($_ENV['CLOUDINARY_VERIFY_SSL'] ?? 'true') === 'false') {
        $cloudinaryConfig['curl'] = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];
        echo "🔧 SSL verification disabled for local testing\n";
    }

    Configuration::instance($cloudinaryConfig);

    // Test connection
    $adminApi = new AdminApi();
    $usage = $adminApi->usage();

    echo "✅ Connection successful!\n";
    echo "📊 Account Info:\n";
    echo "   - Cloud Name: " . $_ENV['CLOUDINARY_CLOUD_NAME'] . "\n";
    echo "   - Credits Used: " . ($usage['credits']['used'] ?? 0) . "\n";
    echo "   - Credits Limit: " . ($usage['credits']['limit'] ?? 0) . "\n";
    echo "   - Storage Used: " . ($usage['storage']['used'] ?? 0) . " bytes\n";
    echo "   - Bandwidth Used: " . ($usage['bandwidth']['used'] ?? 0) . " bytes\n\n";

    echo "🎉 Cloudinary is ready for image uploads!\n";

} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "🔍 Please check your Cloudinary credentials in .env file\n";
    
    // Show current config (without secrets)
    echo "\n📋 Current config:\n";
    echo "   - CLOUDINARY_CLOUD_NAME: " . ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? 'NOT SET') . "\n";
    echo "   - CLOUDINARY_API_KEY: " . (isset($_ENV['CLOUDINARY_API_KEY']) ? 'SET' : 'NOT SET') . "\n";
    echo "   - CLOUDINARY_API_SECRET: " . (isset($_ENV['CLOUDINARY_API_SECRET']) ? 'SET' : 'NOT SET') . "\n";
    echo "   - CLOUDINARY_VERIFY_SSL: " . ($_ENV['CLOUDINARY_VERIFY_SSL'] ?? 'true') . "\n";
}