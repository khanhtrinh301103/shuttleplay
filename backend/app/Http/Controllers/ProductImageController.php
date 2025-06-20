<?php
// File location: backend/app/Http/Controllers/ProductImageController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductImageController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * 🆕 Upload MAIN image for a product (single image only)
     *
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMainImage(Request $request, int $productId)
    {
        try {
            $user = Auth::user();
            $product = Product::findOrFail($productId);

            // Check if user owns the product
            if ($user->role !== 'seller' || $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền upload ảnh cho sản phẩm này'
                ], 403);
            }

            // Validate request - ONLY 1 main image allowed
            $request->validate([
                'main_image' => [
                    'required',
                    'image',
                    'mimes:jpeg,jpg,png,webp,gif',
                    'max:' . (config('cloudinary.max_file_size', 10485760) / 1024) // Convert to KB
                ]
            ], [
                'main_image.required' => 'Vui lòng chọn ảnh chính',
                'main_image.image' => 'File phải là ảnh',
                'main_image.mimes' => 'Ảnh phải có định dạng: jpeg, jpg, png, webp, gif',
                'main_image.max' => 'Kích thước ảnh không được vượt quá ' . round(config('cloudinary.max_file_size', 10485760) / 1024 / 1024, 2) . 'MB',
            ]);

            DB::beginTransaction();

            try {
                // Upload new main image to Cloudinary
                $uploadResult = $this->cloudinaryService->uploadImage(
                    $request->file('main_image'),
                    $productId
                );

                if (!$uploadResult['success']) {
                    throw new \Exception('Có lỗi khi upload ảnh chính: ' . $uploadResult['error']);
                }

                // Check if product already has a main image
                $existingMainImage = $product->images()->where('is_main', true)->first();

                if ($existingMainImage) {
                    // Delete old main image from Cloudinary
                    $oldPublicId = $this->cloudinaryService->extractPublicId($existingMainImage->image_url);
                    if ($oldPublicId) {
                        try {
                            $this->cloudinaryService->deleteImage($oldPublicId);
                        } catch (\Exception $e) {
                            \Log::warning('Failed to delete old main image from Cloudinary: ' . $e->getMessage());
                        }
                    }

                    // Update existing main image record
                    $existingMainImage->update([
                        'image_url' => $uploadResult['secure_url'],
                        'updated_at' => now()
                    ]);

                    $mainImage = $existingMainImage;
                } else {
                    // Create new main image record
                    $mainImage = ProductImage::create([
                        'product_id' => $productId,
                        'image_url' => $uploadResult['secure_url'],
                        'is_main' => true
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Upload ảnh chính thành công',
                    'data' => [
                        'main_image' => $mainImage,
                        'cloudinary_info' => [
                            'public_id' => $uploadResult['public_id'],
                            'width' => $uploadResult['width'],
                            'height' => $uploadResult['height'],
                            'format' => $uploadResult['format'],
                            'bytes' => $uploadResult['bytes']
                        ],
                        'product' => $product->load(['images', 'category'])
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollback();

                // Clean up uploaded image from Cloudinary if database save failed
                if (isset($uploadResult['public_id'])) {
                    try {
                        $this->cloudinaryService->deleteImage($uploadResult['public_id']);
                    } catch (\Exception $cleanupError) {
                        \Log::warning('Failed to cleanup Cloudinary image: ' . $cleanupError->getMessage());
                    }
                }

                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload ảnh chính thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 Upload SECONDARY images for a product (multiple images)
     *
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSecondaryImages(Request $request, int $productId)
    {
        try {
            $user = Auth::user();
            $product = Product::findOrFail($productId);

            // Check if user owns the product
            if ($user->role !== 'seller' || $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền upload ảnh cho sản phẩm này'
                ], 403);
            }

            // Validate request - Multiple secondary images
            $request->validate([
                'secondary_images' => [
                    'required',
                    'array',
                    'min:1',
                    'max:' . (config('cloudinary.max_files_per_product', 10) - 1) // -1 for main image
                ],
                'secondary_images.*' => [
                    'required',
                    'image',
                    'mimes:jpeg,jpg,png,webp,gif',
                    'max:' . (config('cloudinary.max_file_size', 10485760) / 1024) // Convert to KB
                ]
            ], [
                'secondary_images.required' => 'Vui lòng chọn ít nhất một ảnh phụ',
                'secondary_images.array' => 'Dữ liệu ảnh không hợp lệ',
                'secondary_images.min' => 'Vui lòng chọn ít nhất một ảnh phụ',
                'secondary_images.max' => 'Tối đa ' . (config('cloudinary.max_files_per_product', 10) - 1) . ' ảnh phụ cho mỗi sản phẩm',
                'secondary_images.*.required' => 'File ảnh không được để trống',
                'secondary_images.*.image' => 'File phải là ảnh',
                'secondary_images.*.mimes' => 'Ảnh phải có định dạng: jpeg, jpg, png, webp, gif',
                'secondary_images.*.max' => 'Kích thước ảnh không được vượt quá ' . round(config('cloudinary.max_file_size', 10485760) / 1024 / 1024, 2) . 'MB',
            ]);

            // Check current secondary image count
            $currentSecondaryCount = $product->images()->where('is_main', false)->count();
            $newImageCount = count($request->file('secondary_images'));
            $maxSecondaryImages = config('cloudinary.max_files_per_product', 10) - 1; // -1 for main image

            if (($currentSecondaryCount + $newImageCount) > $maxSecondaryImages) {
                return response()->json([
                    'success' => false,
                    'message' => "Sản phẩm chỉ được có tối đa {$maxSecondaryImages} ảnh phụ. Hiện tại có {$currentSecondaryCount} ảnh phụ."
                ], 422);
            }

            $uploadedImages = [];

            DB::beginTransaction();

            try {
                // Upload images to Cloudinary
                $uploadResult = $this->cloudinaryService->uploadMultipleImages(
                    $request->file('secondary_images'),
                    $productId
                );

                if (!$uploadResult['success']) {
                    throw new \Exception('Có lỗi khi upload ảnh phụ: ' . json_encode($uploadResult['errors']));
                }

                // Save image records to database (ALL are secondary images)
                foreach ($uploadResult['uploaded'] as $cloudinaryResult) {
                    $productImage = ProductImage::create([
                        'product_id' => $productId,
                        'image_url' => $cloudinaryResult['secure_url'],
                        'is_main' => false // ALL secondary images
                    ]);

                    $uploadedImages[] = $productImage;
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Upload ảnh phụ thành công',
                    'data' => [
                        'secondary_images' => $uploadedImages,
                        'total_uploaded' => count($uploadedImages),
                        'cloudinary_info' => array_map(function($result) {
                            return [
                                'public_id' => $result['public_id'],
                                'width' => $result['width'],
                                'height' => $result['height'],
                                'format' => $result['format'],
                                'bytes' => $result['bytes']
                            ];
                        }, $uploadResult['uploaded']),
                        'product' => $product->load(['images', 'category'])
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollback();

                // Clean up uploaded images from Cloudinary if database save failed
                if (!empty($uploadResult['uploaded'])) {
                    $publicIds = array_column($uploadResult['uploaded'], 'public_id');
                    try {
                        $this->cloudinaryService->deleteMultipleImages($publicIds);
                    } catch (\Exception $cleanupError) {
                        \Log::warning('Failed to cleanup Cloudinary images: ' . $cleanupError->getMessage());
                    }
                }

                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload ảnh phụ thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an image
     *
     * @param int $productId
     * @param int $imageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(int $productId, int $imageId)
    {
        try {
            $user = Auth::user();
            $product = Product::findOrFail($productId);
            $image = ProductImage::where('product_id', $productId)->findOrFail($imageId);

            // Check if user owns the product
            if ($user->role !== 'seller' || $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa ảnh này'
                ], 403);
            }

            // Prevent deletion of main image if there are no other images
            if ($image->is_main && $product->images()->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa ảnh chính duy nhất. Vui lòng upload ảnh chính mới trước khi xóa.'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Extract public ID from Cloudinary URL
                $publicId = $this->cloudinaryService->extractPublicId($image->image_url);

                // Delete from Cloudinary
                if ($publicId) {
                    try {
                        $this->cloudinaryService->deleteImage($publicId);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete image from Cloudinary: ' . $e->getMessage());
                    }
                }

                // Delete from database
                $wasMain = $image->is_main;
                $image->delete();

                // If this was the main image, promote first secondary image to main
                if ($wasMain) {
                    $newMainImage = $product->images()->where('is_main', false)->first();
                    if ($newMainImage) {
                        $newMainImage->update(['is_main' => true]);
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $wasMain ? 'Xóa ảnh chính thành công' : 'Xóa ảnh phụ thành công',
                    'data' => [
                        'deleted_image_type' => $wasMain ? 'main' : 'secondary',
                        'product' => $product->load(['images', 'category'])
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xóa ảnh thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set main image for product (promote secondary to main)
     *
     * @param int $productId
     * @param int $imageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function setMainImage(int $productId, int $imageId)
    {
        try {
            $user = Auth::user();
            $product = Product::findOrFail($productId);
            $image = ProductImage::where('product_id', $productId)->findOrFail($imageId);

            // Check if user owns the product
            if ($user->role !== 'seller' || $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thay đổi ảnh chính'
                ], 403);
            }

            // Check if image is already main
            if ($image->is_main) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ảnh này đã là ảnh chính'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Remove main status from all images
                $product->images()->update(['is_main' => false]);

                // Set new main image
                $image->update(['is_main' => true]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Đã đặt ảnh chính thành công',
                    'data' => [
                        'main_image' => $image,
                        'product' => $product->load(['images', 'category'])
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đặt ảnh chính thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transformed image URLs
     *
     * @param int $productId
     * @param int $imageId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransformedUrls(int $productId, int $imageId, Request $request)
    {
        try {
            $image = ProductImage::where('product_id', $productId)->findOrFail($imageId);
            
            $publicId = $this->cloudinaryService->extractPublicId($image->image_url);
            
            if (!$publicId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo URL biến đổi cho ảnh này'
                ], 400);
            }

            $transformations = [];
            $availableSizes = ['thumbnail', 'medium', 'large'];

            foreach ($availableSizes as $size) {
                $transformations[$size] = $this->cloudinaryService->getTransformedUrl($publicId, $size);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy URL biến đổi thành công',
                'data' => [
                    'image_id' => $imageId,
                    'image_type' => $image->is_main ? 'main' : 'secondary',
                    'original_url' => $image->image_url,
                    'transformations' => $transformations
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy URL biến đổi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product images summary
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductImages(int $productId)
    {
        try {
            $user = Auth::user();
            $product = Product::findOrFail($productId);

            // Check if user owns the product
            if ($user->role !== 'seller' || $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem ảnh sản phẩm này'
                ], 403);
            }

            $mainImage = $product->images()->where('is_main', true)->first();
            $secondaryImages = $product->images()->where('is_main', false)->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách ảnh thành công',
                'data' => [
                    'main_image' => $mainImage,
                    'secondary_images' => $secondaryImages,
                    'total_images' => $product->images()->count(),
                    'max_secondary_images' => config('cloudinary.max_files_per_product', 10) - 1,
                    'can_add_more_secondary' => $secondaryImages->count() < (config('cloudinary.max_files_per_product', 10) - 1)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách ảnh',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}