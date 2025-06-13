<?php
// File location: backend/app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock_qty',
        'category_id',
        'seller_id',
        'published',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'stock_qty' => 'integer',
        'published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the seller that owns the product.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the images for the product.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the main image for the product.
     */
    public function mainImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the cart items for the product.
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the wishlist items for the product.
     */
    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Get the reviews for the product.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the messages related to the product.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Scope a query to only include published products.
     */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    /**
     * Scope a query to only include unpublished products.
     */
    public function scopeUnpublished($query)
    {
        return $query->where('published', false);
    }

    /**
     * Scope a query to only include products in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_qty', '>', 0);
    }

    /**
     * Scope a query to only include products out of stock.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock_qty', 0);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by seller.
     */
    public function scopeBySeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope a query to filter by price range.
     */
    public function scopePriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    /**
     * Check if product is in stock.
     */
    public function isInStock(): bool
    {
        return $this->stock_qty > 0;
    }

    /**
     * Check if product is published.
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * Get the main image URL or null.
     */
    public function getMainImageUrlAttribute()
    {
        return $this->mainImage?->image_url;
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2) . ' VND';
    }

    /**
     * Get stock status.
     */
    public function getStockStatusAttribute()
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
     * Calculate average rating for the product.
     */
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Get total reviews count.
     */
    public function getReviewsCountAttribute()
    {
        return $this->reviews()->count();
    }
}