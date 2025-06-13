<?php
// File location: backend/app/Services/ProductService.php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Tạo sản phẩm mới với images
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

            // Xử lý images nếu có
            if (isset($data['images']) && is_array($data['images']) && count($data['images']) > 0) {
                $this->attachImages($product, $data['images'], $data['main_image_index'] ?? 0);
            }

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

            // Xử lý images nếu có trong data update
            if (isset($data['images']) && is_array($data['images'])) {
                // Xóa tất cả images cũ
                $product->images()->delete();
                
                // Thêm images mới
                if (count($data['images']) > 0) {
                    $this->attachImages($product, $data['images'], $data['main_image_index'] ?? 0);
                }
            }

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
            // Xóa images trước (vì có foreign key constraint)
            $product->images()->delete();
            
            // Xóa sản phẩm
            return $product->delete();
        });
    }

    /**
     * Attach images cho sản phẩm
     *
     * @param Product $product
     * @param array $imageUrls
     * @param int $mainImageIndex
     * @return void
     */
    private function attachImages(Product $product, array $imageUrls, int $mainImageIndex = 0): void
    {
        foreach ($imageUrls as $index => $imageUrl) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $imageUrl,
                'is_main' => $index === $mainImageIndex
            ]);
        }
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
     * Thêm một image mới cho sản phẩm
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
        $image = $product->images()->find($imageId);
        
        if (!$image) {
            throw new \Exception('Image không tồn tại hoặc không thuộc về sản phẩm này');
        }

        return $image->delete();
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

        return [
            'total_products' => $totalProducts,
            'published_products' => $publishedProducts,
            'unpublished_products' => $unpublishedProducts,
            'total_inventory_value' => $totalValue
        ];
    }
}