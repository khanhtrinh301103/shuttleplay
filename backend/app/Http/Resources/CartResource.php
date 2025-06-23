<?php
// File location: backend/app/Http/Resources/CartResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'user_id' => $this->user_id,
            'items' => CartItemResource::collection($this->whenLoaded('itemsWithProducts')),
            'summary' => [
                'total_items' => $this->getTotalItemsAttribute(),
                'total_unique_products' => $this->whenLoaded('itemsWithProducts', function () {
                    return $this->itemsWithProducts->count();
                }, 0),
                'total_amount' => [
                    'raw' => $this->getTotalAmountAttribute(),
                    'formatted' => number_format($this->getTotalAmountAttribute(), 0, ',', '.') . ' VND',
                    'currency' => 'VND'
                ],
                'items_by_seller' => $this->when($this->relationLoaded('itemsWithProducts'), function () {
                    return $this->getSummary()['items_by_seller'];
                })
            ],
            'status' => [
                'is_empty' => $this->isEmpty(),
                'has_unavailable_items' => $this->whenLoaded('itemsWithProducts', function () {
                    return $this->itemsWithProducts->contains(function ($item) {
                        return !$item->isAvailable();
                    });
                }, false),
                'has_out_of_stock_items' => $this->whenLoaded('itemsWithProducts', function () {
                    return $this->itemsWithProducts->contains(function ($item) {
                        return $item->exceedsStock();
                    });
                }, false)
            ],
            'timestamps' => [
                'created_at' => $this->created_at->format('d/m/Y H:i'),
                'updated_at' => $this->updated_at->format('d/m/Y H:i'),
                'created_at_iso' => $this->created_at->toISOString(),
                'updated_at_iso' => $this->updated_at->toISOString(),
            ]
        ];
    }
}

class CartItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_name' => $this->product ? $this->product->name : 'Sản phẩm đã bị xóa',
            'product_image' => $this->getMainImage(),
            'seller' => [
                'id' => $this->product ? $this->product->seller_id : null,
                'name' => $this->product && $this->product->seller ? $this->product->seller->name : 'Seller không xác định'
            ],
            'quantity' => $this->quantity,
            'price' => [
                'unit_price' => [
                    'raw' => $this->product ? $this->product->price : 0,
                    'formatted' => $this->product ? number_format($this->product->price, 0, ',', '.') . ' VND' : '0 VND',
                    'currency' => 'VND'
                ],
                'total_price' => [
                    'raw' => $this->getTotalPriceAttribute(),
                    'formatted' => number_format($this->getTotalPriceAttribute(), 0, ',', '.') . ' VND',
                    'currency' => 'VND'
                ]
            ],
            'stock' => [
                'available_quantity' => $this->product ? $this->product->stock_qty : 0,
                'is_in_stock' => $this->product ? $this->product->stock_qty >= $this->quantity : false,
                'exceeds_stock' => $this->exceedsStock(),
                'max_available' => $this->getMaxAvailableQuantity()
            ],
            'status' => [
                'is_available' => $this->isAvailable(),
                'is_published' => $this->isProductPublished(),
                'can_purchase' => $this->isAvailable() && !$this->exceedsStock()
            ],
            'product_url' => $this->product ? url("/products/{$this->product->id}") : null,
            'timestamps' => [
                'added_at' => $this->created_at->format('d/m/Y H:i'),
                'added_at_iso' => $this->created_at->toISOString(),
            ]
        ];
    }

    /**
     * Get main image with multiple sizes
     */
    private function getMainImage()
    {
        if (!$this->product || !$this->product->relationLoaded('images')) {
            // Load images if not already loaded
            $this->product?->load('images');
        }

        $mainImageUrl = $this->product ? $this->product->getMainImageUrlAttribute() : null;

        if (!$mainImageUrl) {
            return [
                'url' => null,
                'alt' => $this->product ? $this->product->name : 'Sản phẩm đã bị xóa',
                'sizes' => [
                    'thumbnail' => null,
                    'small' => null,
                    'medium' => null,
                    'original' => null
                ]
            ];
        }

        return [
            'url' => $mainImageUrl,
            'alt' => $this->product->name,
            'sizes' => [
                'thumbnail' => $this->getCloudinaryTransform($mainImageUrl, 'w_100,h_100,c_fill'),
                'small' => $this->getCloudinaryTransform($mainImageUrl, 'w_200,h_200,c_fill'),
                'medium' => $this->getCloudinaryTransform($mainImageUrl, 'w_400,h_400,c_fill'),
                'original' => $mainImageUrl
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
}