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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => [
                'raw' => $this->price,
                'formatted' => number_format($this->price, 0, ',', '.') . ' VND',
                'currency' => 'VND'
            ],
            'stock' => [
                'quantity' => $this->stock_qty,
                'status' => $this->getStockStatus(),
                'is_available' => $this->isInStock()
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'seller' => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
                // Không expose email của seller cho security
            ],
            'images' => $this->formatImages(),
            'main_image' => $this->getMainImageData(),
            'reviews' => [
                'average_rating' => round($this->getAverageRating(), 1),
                'total_count' => $this->getReviewsCount(),
                'reviews_data' => $this->when($this->relationLoaded('reviews'), 
                    function() {
                        return $this->reviews->map(function($review) {
                            return [
                                'id' => $review->id,
                                'rating' => $review->rating,
                                'comment' => $review->comment,
                                'reviewer_name' => $review->user ? $review->user->name : 'Anonymous',
                                'created_at' => $review->created_at->format('d/m/Y H:i'),
                            ];
                        });
                    }
                )
            ],
            'status' => [
                'published' => $this->published,
                'available' => $this->isInStock() && $this->published
            ],
            'timestamps' => [
                'created_at' => $this->created_at->format('d/m/Y H:i'),
                'updated_at' => $this->updated_at->format('d/m/Y H:i'),
                'created_at_iso' => $this->created_at->toISOString(),
                'updated_at_iso' => $this->updated_at->toISOString(),
            ],
            // SEO và sharing data
            'seo' => [
                'title' => $this->name,
                'description' => $this->description ? substr(strip_tags($this->description), 0, 160) : null,
                'image' => $this->getMainImageUrl(),
                'url' => url("/products/{$this->id}")
            ]
        ];
    }

    /**
     * Format images data with multiple sizes
     */
    private function formatImages()
    {
        if (!$this->relationLoaded('images') || $this->images->isEmpty()) {
            return [];
        }

        return $this->images->map(function($image) {
            $baseUrl = $image->image_url;
            
            return [
                'id' => $image->id,
                'url' => $baseUrl,
                'is_main' => $image->is_main,
                'alt' => $this->name, // Sử dụng tên sản phẩm làm alt text
                // Các size khác nhau cho responsive images
                'sizes' => [
                    'thumbnail' => $this->getCloudinaryTransform($baseUrl, 'w_150,h_150,c_fill'),
                    'small' => $this->getCloudinaryTransform($baseUrl, 'w_300,h_300,c_fill'),
                    'medium' => $this->getCloudinaryTransform($baseUrl, 'w_600,h_600,c_fill'),
                    'large' => $this->getCloudinaryTransform($baseUrl, 'w_1200,h_1200,c_fit'),
                    'original' => $baseUrl
                ]
            ];
        })->toArray();
    }

    /**
     * Get main image data
     */
    private function getMainImageData()
    {
        $mainImage = $this->getMainImageUrl();
        
        if (!$mainImage) {
            return [
                'url' => null,
                'alt' => $this->name,
                'sizes' => [
                    'thumbnail' => null,
                    'small' => null,
                    'medium' => null,
                    'large' => null,
                    'original' => null
                ]
            ];
        }

        return [
            'url' => $mainImage,
            'alt' => $this->name,
            'sizes' => [
                'thumbnail' => $this->getCloudinaryTransform($mainImage, 'w_150,h_150,c_fill'),
                'small' => $this->getCloudinaryTransform($mainImage, 'w_300,h_300,c_fill'),
                'medium' => $this->getCloudinaryTransform($mainImage, 'w_600,h_600,c_fill'),
                'large' => $this->getCloudinaryTransform($mainImage, 'w_1200,h_1200,c_fit'),
                'original' => $mainImage
            ]
        ];
    }

    /**
     * Get Cloudinary transformed URL
     */
    private function getCloudinaryTransform($originalUrl, $transformation)
    {
        if (!$originalUrl || !str_contains($originalUrl, 'cloudinary.com')) {
            return $originalUrl;
        }

        // Insert transformation into Cloudinary URL
        $pattern = '/(\/upload\/)/';
        $replacement = "/upload/{$transformation}/";
        
        return preg_replace($pattern, $replacement, $originalUrl);
    }

    /**
     * Get stock status text
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

    /**
     * Get average rating (fallback if accessor not available)
     */
    private function getAverageRating()
    {
        if (method_exists($this->resource, 'getAverageRatingAttribute')) {
            return $this->average_rating;
        }

        // Fallback calculation
        if ($this->relationLoaded('reviews') && $this->reviews->isNotEmpty()) {
            return $this->reviews->avg('rating');
        }

        return 0;
    }

    /**
     * Get reviews count (fallback if accessor not available)
     */
    private function getReviewsCount()
    {
        if (method_exists($this->resource, 'getReviewsCountAttribute')) {
            return $this->reviews_count;
        }

        // Fallback calculation
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->count();
        }

        return 0;
    }

    /**
     * Get main image URL (fallback if accessor not available)
     */
    private function getMainImageUrl()
    {
        if (method_exists($this->resource, 'getMainImageUrlAttribute')) {
            return $this->main_image_url;
        }

        // Fallback
        if ($this->relationLoaded('images')) {
            $mainImage = $this->images->firstWhere('is_main', true);
            return $mainImage ? $mainImage->image_url : ($this->images->first()?->image_url);
        }

        return null;
    }
}