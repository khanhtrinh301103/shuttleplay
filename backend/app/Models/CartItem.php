<?php
// File location: backend/app/Models/CartItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cart_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Keep timestamps enabled but disable updated_at since table doesn't have this column
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Override updated_at to be null since table doesn't have this column
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * Get the cart that owns the cart item.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * Get the product for the cart item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit price from product
     */
    public function getUnitPriceAttribute()
    {
        return $this->product ? $this->product->price : 0;
    }

    /**
     * Calculate total price (quantity * unit price)
     */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Get product name
     */
    public function getProductNameAttribute()
    {
        return $this->product ? $this->product->name : null;
    }

    /**
     * Get product main image URL
     */
    public function getProductImageAttribute()
    {
        return $this->product ? $this->product->getMainImageUrlAttribute() : null;
    }

    /**
     * Get seller ID
     */
    public function getSellerIdAttribute()
    {
        return $this->product ? $this->product->seller_id : null;
    }

    /**
     * Get seller name
     */
    public function getSellerNameAttribute()
    {
        return $this->product && $this->product->seller ? $this->product->seller->name : null;
    }

    /**
     * Check if product is still available
     */
    public function isAvailable(): bool
    {
        return $this->product && 
               $this->product->published && 
               $this->product->stock_qty >= $this->quantity;
    }

    /**
     * Check if quantity exceeds stock
     */
    public function exceedsStock(): bool
    {
        return $this->product && $this->quantity > $this->product->stock_qty;
    }

    /**
     * Get maximum available quantity
     */
    public function getMaxAvailableQuantity(): int
    {
        return $this->product ? $this->product->stock_qty : 0;
    }

    /**
     * Check if product is published
     */
    public function isProductPublished(): bool
    {
        return $this->product && $this->product->published;
    }

    /**
     * Scope to only items with available products
     */
    public function scopeAvailable($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('published', true)->where('stock_qty', '>', 0);
        });
    }

    /**
     * Scope to only items with published products
     */
    public function scopePublished($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('published', true);
        });
    }
}