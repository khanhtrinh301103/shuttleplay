<?php
// File location: backend/app/Http/Resources/CustomerProductResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Get main image URL or null if no main image exists
        $mainImageUrl = $this->getMainImageUrl();
        
        // Get all images for detailed view
        $allImages = $this->whenLoaded('images', function () {
            return $this->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->image_url,
                    'is_main' => $image->is_main,
                ];
            });
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => [
                'raw' => (float) $this->price,
                'formatted' => number_format($this->price, 0, ',', '.') . ' VND',
            ],
            'stock_qty' => $this->stock_qty,
            'stock_status' => $this->getStockStatus(),
            'is_in_stock' => $this->isInStock(),
            'published' => $this->published,
            
            // Main image handling according to requirements
            'main_image' => [
                'url' => $mainImageUrl,
                'has_image' => !is_null($mainImageUrl),
            ],
            
            // All images (for detail view)
            'images' => $allImages,
            'images_count' => $this->whenLoaded('images', function () {
                return $this->images->count();
            }),
            
            // Category information
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            
            // Seller information (limited for customer view)
            'seller' => $this->whenLoaded('seller', function () {
                return [
                    'id' => $this->seller->id,
                    'name' => $this->seller->name,
                    // Don't expose email or other sensitive seller info to customers
                ];
            }),
            
            // Ratings and reviews (if available)
            'rating' => [
                'average' => round($this->getAverageRatingAttribute(), 1),
                'count' => $this->getReviewsCountAttribute(),
            ],
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Formatted timestamps for display
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at_human' => $this->updated_at?->diffForHumans(),
        ];
    }

    /**
     * Get main image URL according to requirements
     * Returns URL if main image exists, null otherwise
     *
     * @return string|null
     */
    private function getMainImageUrl()
    {
        // Check if images are loaded
        if (!$this->relationLoaded('images')) {
            // If mainImage relation is loaded, use it
            if ($this->relationLoaded('mainImage')) {
                return $this->mainImage?->image_url;
            }
            // Otherwise, try to get from the model's accessor
            return $this->main_image_url;
        }

        // Find the main image from loaded images
        $mainImage = $this->images->firstWhere('is_main', true);
        
        // Return main image URL if exists, null if all is_main are false
        return $mainImage ? $mainImage->image_url : null;
    }

    /**
     * Get stock status text
     *
     * @return string
     */
    private function getStockStatus()
    {
        if ($this->stock_qty > 10) {
            return 'Còn hàng';
        } elseif ($this->stock_qty > 0) {
            return 'Sắp hết hàng';
        } else {
            return 'Hết hàng';
        }
    }
}