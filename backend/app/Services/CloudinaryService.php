<?php
// File location: backend/app/Services/CloudinaryService.php

namespace App\Services;

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Transformation\Resize;
use Cloudinary\Transformation\Quality;
use Cloudinary\Transformation\Format;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CloudinaryService
{
    protected $uploadApi;
    protected $adminApi;
    protected $config;

    public function __construct()
    {
        // Configure Cloudinary
        Configuration::instance([
            'cloud' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key' => config('cloudinary.api_key'),
                'api_secret' => config('cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => config('cloudinary.secure', true)
            ]
        ]);

        $this->uploadApi = new UploadApi();
        $this->adminApi = new AdminApi();
        $this->config = config('cloudinary');
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

            // Upload options
            $options = [
                'folder' => $this->config['folder'],
                'public_id' => $publicId,
                'overwrite' => $this->config['overwrite'],
                'unique_filename' => $this->config['unique_filename'],
                'resource_type' => 'image',
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'allowed_formats' => $this->config['allowed_formats'],
                'max_bytes' => $this->config['max_file_size']
            ];

            // Upload to Cloudinary
            $result = $this->uploadApi->upload($file->getRealPath(), $options);

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
            $result = $this->uploadApi->destroy($publicId);

            return [
                'success' => $result['result'] === 'ok',
                'result' => $result['result'],
                'public_id' => $publicId
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to delete image: ' . $e->getMessage());
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

        $url = cloudinary_url($publicId, [
            'width' => $transformations['width'],
            'height' => $transformations['height'],
            'crop' => $transformations['crop'],
            'quality' => $transformations['quality'],
            'fetch_format' => $transformations['fetch_format'],
            'secure' => true
        ]);

        return $url;
    }

    /**
     * Extract public ID from Cloudinary URL
     *
     * @param string $url
     * @return string|null
     */
    public function extractPublicId(string $url): ?string
    {
        // Pattern to match Cloudinary URLs and extract public ID
        $pattern = '/\/(?:v\d+\/)?(?:.*\/)?([^\/]+)\.[a-zA-Z]{3,4}$/';
        
        if (preg_match($pattern, $url, $matches)) {
            // Remove folder prefix if exists
            $publicIdWithFolder = $matches[1];
            $folder = $this->config['folder'];
            
            if (strpos($url, $folder) !== false) {
                return $folder . '/' . $publicIdWithFolder;
            }
            
            return $publicIdWithFolder;
        }

        return null;
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
            $usage = $this->adminApi->usage();
            
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
}