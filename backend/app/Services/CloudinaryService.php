<?php
// File location: backend/app/Services/CloudinaryService.php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CloudinaryService
{
    protected $httpClient;
    protected $config;
    protected $cloudName;
    protected $apiKey;
    protected $apiSecret;
    protected $baseUrl;

    public function __construct()
    {
        $this->config = config('cloudinary');
        $this->cloudName = config('cloudinary.cloud_name');
        $this->apiKey = config('cloudinary.api_key');
        $this->apiSecret = config('cloudinary.api_secret');
        $this->baseUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}";

        // Create HTTP client with SSL disabled for development
        $this->httpClient = new Client([
            'verify' => false, // Disable SSL verification completely
            'timeout' => 30,
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ]
        ]);
    }

    /**
     * Upload single image to Cloudinary
     *
     * @param UploadedFile $file
     * @param int $productId
     * @param string|null $customPublicId
     * @return array
     * @throws Exception
     */
    public function uploadImage(UploadedFile $file, int $productId, ?string $customPublicId = null): array
    {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique public ID
            $publicId = $customPublicId ?? $this->generatePublicId($productId);

            // Prepare form data using upload preset (simplest approach)
            $formData = [
                [
                    'name' => 'file',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName()
                ],
                [
                    'name' => 'upload_preset',
                    'contents' => config('cloudinary.upload_preset', 'shuttleplay_products')
                ],
                [
                    'name' => 'public_id',
                    'contents' => $publicId
                ],
                [
                    'name' => 'folder',
                    'contents' => $this->config['folder']
                ]
            ];

            // Upload to Cloudinary using upload preset (no auth needed)
            $response = $this->httpClient->post("{$this->baseUrl}/image/upload", [
                'multipart' => $formData
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url'],
                'url' => $result['url'],
                'format' => $result['format'],
                'width' => $result['width'],
                'height' => $result['height'],
                'bytes' => $result['bytes'],
                'version' => $result['version'],
                'created_at' => $result['created_at']
            ];

        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody() : 'Unknown error';
            throw new Exception('Failed to upload image: ' . $errorBody);
        } catch (Exception $e) {
            throw new Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple images
     *
     * @param array $files Array of UploadedFile
     * @param int $productId
     * @return array
     */
    public function uploadMultipleImages(array $files, int $productId): array
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $result = $this->uploadImage($file, $productId);
                $results[] = $result;
            } catch (Exception $e) {
                $errors[] = [
                    'file_index' => $index,
                    'file_name' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => empty($errors),
            'uploaded' => $results,
            'errors' => $errors,
            'total_uploaded' => count($results),
            'total_failed' => count($errors)
        ];
    }

    /**
     * Delete image from Cloudinary
     *
     * @param string $publicId
     * @return array
     * @throws Exception
     */
    public function deleteImage(string $publicId): array
    {
        try {
            $response = $this->httpClient->post("{$this->baseUrl}/image/destroy", [
                'auth' => [$this->apiKey, $this->apiSecret],
                'form_params' => [
                    'public_id' => $publicId
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'success' => $result['result'] === 'ok',
                'result' => $result['result'],
                'public_id' => $publicId
            ];

        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody() : 'Unknown error';
            throw new Exception('Failed to delete image: ' . $errorBody);
        }
    }

    /**
     * Delete multiple images
     *
     * @param array $publicIds
     * @return array
     */
    public function deleteMultipleImages(array $publicIds): array
    {
        $results = [];
        $errors = [];

        foreach ($publicIds as $publicId) {
            try {
                $result = $this->deleteImage($publicId);
                $results[] = $result;
            } catch (Exception $e) {
                $errors[] = [
                    'public_id' => $publicId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => empty($errors),
            'deleted' => $results,
            'errors' => $errors,
            'total_deleted' => count($results),
            'total_failed' => count($errors)
        ];
    }

    /**
     * Get transformed image URL
     *
     * @param string $publicId
     * @param string $transformation (thumbnail, medium, large)
     * @return string
     */
    public function getTransformedUrl(string $publicId, string $transformation = 'medium'): string
    {
        $transformations = $this->config['transformations'][$transformation] ?? $this->config['transformations']['medium'];
        
        // Build Cloudinary URL manually
        $baseUrl = "https://res.cloudinary.com/{$this->cloudName}/image/upload";
        
        // Build transformation string
        $transformationParams = [];
        
        if (isset($transformations['width'])) {
            $transformationParams[] = "w_{$transformations['width']}";
        }
        
        if (isset($transformations['height'])) {
            $transformationParams[] = "h_{$transformations['height']}";
        }
        
        if (isset($transformations['crop'])) {
            $transformationParams[] = "c_{$transformations['crop']}";
        }
        
        if (isset($transformations['quality'])) {
            $transformationParams[] = "q_{$transformations['quality']}";
        }
        
        if (isset($transformations['fetch_format'])) {
            $transformationParams[] = "f_{$transformations['fetch_format']}";
        }

        $transformationString = implode(',', $transformationParams);
        
        // Construct final URL
        if (!empty($transformationString)) {
            return "{$baseUrl}/{$transformationString}/{$publicId}";
        }
        
        return "{$baseUrl}/{$publicId}";
    }

    /**
     * Extract public ID from Cloudinary URL
     *
     * @param string $url
     * @return string|null
     */
    public function extractPublicId(string $url): ?string
    {
        // Check if it's a valid Cloudinary URL
        if (!str_contains($url, 'cloudinary.com')) {
            return null;
        }

        // Remove base URL part
        $pattern = '/.*\/image\/upload\//';
        $remaining = preg_replace($pattern, '', $url);
        
        if (!$remaining) {
            return null;
        }
        
        // Split by '/' to handle version and transformations
        $parts = explode('/', $remaining);
        
        // The last part should contain the public_id with extension
        $lastPart = end($parts);
        
        // Remove file extension
        $publicId = preg_replace('/\.[^.]+$/', '', $lastPart);
        
        // If we have a folder structure, include the folder path
        if (count($parts) > 1) {
            // Check if first part is version (starts with 'v' followed by numbers)
            $firstPart = $parts[0];
            if (preg_match('/^v\d+$/', $firstPart)) {
                array_shift($parts); // Remove version
            }
            
            // Find where transformations end and public_id path begins
            $publicIdParts = [];
            
            foreach ($parts as $part) {
                // If part contains transformation parameters (w_, h_, c_, q_, f_, etc.)
                if (preg_match('/^[a-z]_/', $part) || str_contains($part, ',')) {
                    continue;
                }
                
                $publicIdParts[] = $part;
            }
            
            // Remove extension from last part
            if (!empty($publicIdParts)) {
                $lastIndex = count($publicIdParts) - 1;
                $publicIdParts[$lastIndex] = preg_replace('/\.[^.]+$/', '', $publicIdParts[$lastIndex]);
                return implode('/', $publicIdParts);
            }
        }
        
        return $publicId;
    }

    /**
     * Generate unique public ID for product image
     *
     * @param int $productId
     * @return string
     */
    private function generatePublicId(int $productId): string
    {
        return 'product_' . $productId . '_' . Str::random(8) . '_' . time();
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }

        // Check file size
        if ($file->getSize() > $this->config['max_file_size']) {
            $maxSizeMB = round($this->config['max_file_size'] / 1024 / 1024, 2);
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->config['allowed_formats'])) {
            $allowedFormats = implode(', ', $this->config['allowed_formats']);
            throw new Exception("File format not allowed. Allowed formats: {$allowedFormats}");
        }

        // Check if it's actually an image
        $mimeType = $file->getMimeType();
        if (!str_starts_with($mimeType, 'image/')) {
            throw new Exception('File must be an image');
        }
    }

    /**
     * Get storage info from Cloudinary
     *
     * @return array
     */
    public function getStorageInfo(): array
    {
        try {
            $response = $this->httpClient->get("{$this->baseUrl}/usage", [
                'auth' => [$this->apiKey, $this->apiSecret]
            ]);

            $usage = json_decode($response->getBody(), true);
            
            return [
                'success' => true,
                'credits_used' => $usage['credits']['used'] ?? 0,
                'credits_limit' => $usage['credits']['limit'] ?? 0,
                'storage_used' => $usage['storage']['used'] ?? 0,
                'bandwidth_used' => $usage['bandwidth']['used'] ?? 0,
                'transformations_used' => $usage['transformations']['used'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get direct Cloudinary URL without transformations
     *
     * @param string $publicId
     * @return string
     */
    public function getDirectUrl(string $publicId): string
    {
        return "https://res.cloudinary.com/{$this->cloudName}/image/upload/{$publicId}";
    }

    /**
     * Build custom transformation URL
     *
     * @param string $publicId
     * @param array $customTransformations
     * @return string
     */
    public function getCustomTransformedUrl(string $publicId, array $customTransformations = []): string
    {
        $baseUrl = "https://res.cloudinary.com/{$this->cloudName}/image/upload";
        
        if (empty($customTransformations)) {
            return "{$baseUrl}/{$publicId}";
        }
        
        $transformationParams = [];
        
        foreach ($customTransformations as $key => $value) {
            switch ($key) {
                case 'width':
                    $transformationParams[] = "w_{$value}";
                    break;
                case 'height':
                    $transformationParams[] = "h_{$value}";
                    break;
                case 'crop':
                    $transformationParams[] = "c_{$value}";
                    break;
                case 'quality':
                    $transformationParams[] = "q_{$value}";
                    break;
                case 'format':
                    $transformationParams[] = "f_{$value}";
                    break;
                case 'effect':
                    $transformationParams[] = "e_{$value}";
                    break;
                case 'angle':
                    $transformationParams[] = "a_{$value}";
                    break;
                case 'radius':
                    $transformationParams[] = "r_{$value}";
                    break;
            }
        }
        
        $transformationString = implode(',', $transformationParams);
        return "{$baseUrl}/{$transformationString}/{$publicId}";
    }
}