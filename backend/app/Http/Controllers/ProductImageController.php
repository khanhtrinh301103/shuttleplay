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
     * Upload images for a product
     *
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImages(Request $request, int $productId)
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

            // Validate request
            $request->validate([
                'images' => [
                    'required',
                    'array',
                    'min:1',
                    'max:' . config('cloudinary.max_files_per_product', 10)
                ],
                'images.*' => [
                    'required',
                    'image',
                    'mimes:jpeg,jpg,png,webp,gif',
                    'max:' . (config('cloudinary.max_file_size', 10485760) / 1024) // Convert to KB
                ],
                'main_image_index' => [
                    'sometimes',
                    'integer',
                    'min:0'
                ]
            ], [
                'images.required' => 'Vui lòng chọn ít nhất một ảnh',
                'images.array' => 'Dữ liệu ảnh không hợp lệ',
                'images.min' => 'Vui lòng chọn ít nhất một ảnh',
                'images.max' => 'Tối đa ' . config('cloudinary.max_files_per_product', 10) . ' ảnh cho mỗi sản phẩm',
                'images.*.required' => 'File ảnh không được để trống',
                'images.*.image' => 'File phải là ảnh',
                'images.*.mimes' => 'Ảnh phải có định dạng: jpeg, jpg, png, webp, gif',
                'images.*.max' => 'Kích thước ảnh không được vượt quá ' . round(config('cloudinary.max_file_size', 10485760) / 1024 / 1024, 2) . 'MB',
            ]);

            // Check current image count
            $currentImageCount = $product->images()->count();
            $newImageCount = count($request->file('images'));
            $maxImages = config('cloudinary.max_files_per_product', 10);

            if (($currentImageCount + $newImageCount) > $maxImages) {
                return response()->json([
                    'success' => false,
                    'message' => "Sản phẩm chỉ được có tối đa {$maxImages} ảnh. Hiện tại có {$currentImageCount} ảnh."
                ], 422);
            }

            $uploadedImages = [];
            $mainImageIndex = $request->input('main_image_index', 0);

            DB::beginTransaction();

            try {
                // Upload images to Cloudinary
                $uploadResult = $this->cloudinaryService->uploadMultipleImages(
                    $request->file('images'),
                    $productId
                );

                if (!$uploadResult['success']) {
                    throw new \Exception('Có lỗi khi upload ảnh: ' . json_encode($uploadResult['errors']));
                }

                // Save image records to database
                foreach ($uploadResult['uploaded'] as $index => $cloudinaryResult) {
                    $isMain = ($index === $mainImageIndex && $currentImageCount === 0); // Only set main if no existing images

                    $productImage = ProductImage::create([
                        'product_id' => $productId,
                        'image_url' => $cloudinaryResult['secure_url'],
                        'is_main' => $isMain
                    ]);

                    $uploadedImages[] = $productImage;
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Upload ảnh thành công',
                    'data' => [
                        'uploaded_images' => $uploadedImages,
                        'total_uploaded' => count($uploadedImages),
                        'product' => $product->load(['images', 'category'])
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollback();

                // Clean up uploaded images from Cloudinary if database save failed
                if (!empty($uploadResult['uploaded'])) {
                    $publicIds = array_column($uploadResult['uploaded'], 'public_id');
                    $this->cloudinaryService->deleteMultipleImages($publicIds);
                }

                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload ảnh thất bại',
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

            DB::beginTransaction();

            try {
                // Extract public ID from Cloudinary URL
                $publicId = $this->cloudinaryService->extractPublicId($image->image_url);

                // Delete from Cloudinary
                if ($publicId) {
                    $this->cloudinaryService->deleteImage($publicId);
                }

                // Delete from database
                $image->delete();

                // If this was the main image, set another image as main
                if ($image->is_main) {
                    $newMainImage = $product->images()->first();
                    if ($newMainImage) {
                        $newMainImage->update(['is_main' => true]);
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Xóa ảnh thành công',
                    'data' => [
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
     * Set main image for product
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
     * Reorder product images
     *
     * @param int $productId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderImages(int $productId, Request $request)
    {
        try {
            $user = Auth::user();
            $product = Product::findOrFail($productId);

            // Check if user owns the product
            if ($user->role !== 'seller' || $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền sắp xếp lại ảnh'
                ], 403);
            }

            $request->validate([
                'image_order' => 'required|array',
                'image_order.*' => 'required|integer|exists:product_images,id',
                'main_image_id' => 'sometimes|integer|exists:product_images,id'
            ]);

            $imageOrder = $request->input('image_order');
            $mainImageId = $request->input('main_image_id');

            // Verify all images belong to this product
            $productImageIds = $product->images()->pluck('id')->toArray();
            $invalidIds = array_diff($imageOrder, $productImageIds);

            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số ảnh không thuộc về sản phẩm này'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Reset main image status
                $product->images()->update(['is_main' => false]);

                // Update image order and set main image
                foreach ($imageOrder as $order => $imageId) {
                    $updateData = ['updated_at' => now()];
                    
                    if ($mainImageId && $imageId == $mainImageId) {
                        $updateData['is_main'] = true;
                    } elseif (!$mainImageId && $order === 0) {
                        $updateData['is_main'] = true; // First image as main if no main specified
                    }

                    ProductImage::where('id', $imageId)->update($updateData);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Sắp xếp ảnh thành công',
                    'data' => [
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
                'message' => 'Sắp xếp ảnh thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}