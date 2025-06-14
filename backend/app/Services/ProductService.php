<?php
// File location: backend/app/Services/ProductService.php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * Tạo sản phẩm mới (không bao gồm images - sẽ upload riêng)
     *
     * @param array $data
     * @param int $sellerId
     * @return Product
     */
    public function createProduct(array $data, int $sellerId): Product
    {
        return DB::transaction(function () use ($data, $sellerId) {
            // Tạo sản phẩm
            $product = Product::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'stock_qty' => $data['stock_qty'],
                'category_id' => $data['category_id'],
                'seller_id' => $sellerId,
                'published' => $data['published'] ?? false,
            ]);

            return $product;
        });
    }

    /**
     * Cập nhật sản phẩm
     *
     * @param Product $product
     * @param array $data
     * @return Product
     */
    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            // Cập nhật thông tin sản phẩm
            $updateData = [];
            
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            
            if (isset($data['price'])) {
                $updateData['price'] = $data['price'];
            }
            
            if (isset($data['stock_qty'])) {
                $updateData['stock_qty'] = $data['stock_qty'];
            }
            
            if (isset($data['category_id'])) {
                $updateData['category_id'] = $data['category_id'];
            }
            
            if (isset($data['published'])) {
                $updateData['published'] = $data['published'];
            }

            $product->update($updateData);

            return $product->fresh();
        });
    }

    /**
     * Xóa sản phẩm và các images liên quan
     *
     * @param Product $product
     * @return bool
     */
    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Lấy danh sách images để xóa từ Cloudinary
            $images = $product->images()->get();
            $publicIds = [];

            foreach ($images as $image) {
                $publicId = $this->cloudinaryService->extractPublicId($image->image_url);
                if ($publicId) {
                    $publicIds[] = $publicId;
                }
            }

            // Xóa images từ Cloudinary
            if (!empty($publicIds)) {
                try {
                    $this->cloudinaryService->deleteMultipleImages($publicIds);
                } catch (\Exception $e) {
                    // Log error but continue with database deletion
                    \Log::warning('Failed to delete some images from Cloudinary: ' . $e->getMessage());
                }
            }

            // Xóa images từ database
            $product->images()->delete();
            
            // Xóa sản phẩm
            return $product->delete();
        });
    }

    /**
     * Thêm một image mới cho sản phẩm từ uploaded file
     *
     * @param Product $product
     * @param \Illuminate\Http\UploadedFile $file
     * @param bool $isMain
     * @return ProductImage
     */
    public function addImageFromFile(Product $product, $file, bool $isMain = false): ProductImage
    {
        return DB::transaction(function () use ($product, $file, $isMain) {
            // Upload to Cloudinary
            $uploadResult = $this->cloudinaryService->uploadImage($file, $product->id);

            if (!$uploadResult['success']) {
                throw new \Exception('Failed to upload image to Cloudinary');
            }

            // Nếu set làm main image, thì bỏ main của các image khác
            if ($isMain) {
                $product->images()->update(['is_main' => false]);
            }

            return ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $uploadResult['secure_url'],
                'is_main' => $isMain
            ]);
        });
    }

    /**
     * Thêm image từ URL (legacy method - giữ để backward compatibility)
     *
     * @param Product $product
     * @param string $imageUrl
     * @param bool $isMain
     * @return ProductImage
     */
    public function addImage(Product $product, string $imageUrl, bool $isMain = false): ProductImage
    {
        return DB::transaction(function () use ($product, $imageUrl, $isMain) {
            // Nếu set làm main image, thì bỏ main của các image khác
            if ($isMain) {
                $product->images()->update(['is_main' => false]);
            }

            return ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $imageUrl,
                'is_main' => $isMain
            ]);
        });
    }

    /**
     * Xóa một image của sản phẩm
     *
     * @param Product $product
     * @param int $imageId
     * @return bool
     */
    public function removeImage(Product $product, int $imageId): bool
    {
        return DB::transaction(function () use ($product, $imageId) {
            $image = $product->images()->find($imageId);
            
            if (!$image) {
                throw new \Exception('Image không tồn tại hoặc không thuộc về sản phẩm này');
            }

            // Delete from Cloudinary
            $publicId = $this->cloudinaryService->extractPublicId($image->image_url);
            if ($publicId) {
                try {
                    $this->cloudinaryService->deleteImage($publicId);
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete image from Cloudinary: ' . $e->getMessage());
                }
            }

            // Delete from database
            $wasMain = $image->is_main;
            $result = $image->delete();

            // If deleted image was main, set another as main
            if ($wasMain) {
                $newMainImage = $product->images()->first();
                if ($newMainImage) {
                    $newMainImage->update(['is_main' => true]);
                }
            }

            return $result;
        });
    }

    /**
     * Cập nhật ảnh chính cho sản phẩm
     *
     * @param Product $product
     * @param int $imageId
     * @return bool
     */
    public function setMainImage(Product $product, int $imageId): bool
    {
        return DB::transaction(function () use ($product, $imageId) {
            // Kiểm tra image có thuộc về sản phẩm này không
            $image = $product->images()->find($imageId);
            if (!$image) {
                throw new \Exception('Image không thuộc về sản phẩm này');
            }

            // Bỏ is_main của tất cả images khác
            $product->images()->update(['is_main' => false]);
            
            // Set image này là main
            $image->update(['is_main' => true]);
            
            return true;
        });
    }

    /**
     * Lấy thống kê sản phẩm của seller
     *
     * @param int $sellerId
     * @return array
     */
    public function getSellerProductStats(int $sellerId): array
    {
        $totalProducts = Product::where('seller_id', $sellerId)->count();
        $publishedProducts = Product::where('seller_id', $sellerId)->where('published', true)->count();
        $unpublishedProducts = $totalProducts - $publishedProducts;
        $totalValue = Product::where('seller_id', $sellerId)->sum(DB::raw('price * stock_qty'));
        $totalImages = ProductImage::whereHas('product', function($query) use ($sellerId) {
            $query->where('seller_id', $sellerId);
        })->count();

        return [
            'total_products' => $totalProducts,
            'published_products' => $publishedProducts,
            'unpublished_products' => $unpublishedProducts,
            'total_inventory_value' => $totalValue,
            'total_images' => $totalImages
        ];
    }

    /**
     * Bulk delete products with their images
     *
     * @param array $productIds
     * @param int $sellerId
     * @return array
     */
    public function bulkDeleteProducts(array $productIds, int $sellerId): array
    {
        $deletedCount = 0;
        $errors = [];

        foreach ($productIds as $productId) {
            try {
                $product = Product::where('id', $productId)
                    ->where('seller_id', $sellerId)
                    ->first();

                if (!$product) {
                    $errors[] = "Product ID {$productId} not found or not owned by seller";
                    continue;
                }

                $this->deleteProduct($product);
                $deletedCount++;

            } catch (\Exception $e) {
                $errors[] = "Failed to delete product ID {$productId}: " . $e->getMessage();
            }
        }

        return [
            'success' => $deletedCount > 0,
            'deleted_count' => $deletedCount,
            'total_requested' => count($productIds),
            'errors' => $errors
        ];
    }

    /**
     * Search products with filters
     *
     * @param array $filters
     * @param int $sellerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchSellerProducts(array $filters, int $sellerId)
    {
        $query = Product::with(['category', 'images'])
            ->where('seller_id', $sellerId);

        // Apply filters
        if (isset($filters['name']) && !empty($filters['name'])) {
            $query->where('name', 'ILIKE', '%' . $filters['name'] . '%');
        }

        if (isset($filters['category_id']) && !empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['published']) && $filters['published'] !== null) {
            $query->where('published', $filters['published']);
        }

        if (isset($filters['min_price']) && !empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price']) && !empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->where('stock_qty', '>', 0);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        $allowedSortFields = ['created_at', 'updated_at', 'name', 'price', 'stock_qty'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->get();
    }
}