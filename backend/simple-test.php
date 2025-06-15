<?php
// File: backend/simple-test.php
// Simple test without Laravel dependencies

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

// Read .env manually
$envFile = '.env';
$envVars = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with($line, '#')) {
            [$key, $value] = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
}

$cloudName = $envVars['CLOUDINARY_CLOUD_NAME'] ?? '';
$apiKey = $envVars['CLOUDINARY_API_KEY'] ?? '';
$apiSecret = $envVars['CLOUDINARY_API_SECRET'] ?? '';

echo "🔄 Testing Cloudinary with Guzzle HTTP Client...\n";
echo "📡 Cloud Name: {$cloudName}\n\n";

try {
    // Create HTTP client with SSL disabled
    $client = new Client([
        'verify' => false,
        'timeout' => 30,
        'curl' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]
    ]);

    echo "🌐 Making API request...\n";
    
    $response = $client->get("https://api.cloudinary.com/v1_1/{$cloudName}/usage", [
        'auth' => [$apiKey, $apiSecret]
    ]);

    $data = json_decode($response->getBody(), true);
    
    echo "✅ SUCCESS! Cloudinary connection working!\n\n";
    echo "📊 Account Info:\n";
    echo "   - Cloud Name: {$cloudName}\n";
    echo "   - Credits Used: " . ($data['credits']['used'] ?? 0) . "\n";
    echo "   - Credits Limit: " . ($data['credits']['limit'] ?? 0) . "\n";
    echo "   - Storage Used: " . ($data['storage']['used'] ?? 0) . " bytes\n";
    echo "   - Bandwidth Used: " . ($data['bandwidth']['used'] ?? 0) . " bytes\n\n";
    
    echo "🎉 Ready to test image uploads!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
        echo "🔍 Please check your .env file has:\n";
        echo "   - CLOUDINARY_CLOUD_NAME\n";
        echo "   - CLOUDINARY_API_KEY\n";
        echo "   - CLOUDINARY_API_SECRET\n";
    }
}