<?php
// File location: backend/app/Models/Cart.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shopping_carts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the cart.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cart items for the cart.
     */
    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    /**
     * Get cart items with product details
     */
    public function itemsWithProducts()
    {
        return $this->hasMany(CartItem::class, 'cart_id')->with(['product', 'product.images', 'product.seller']);
    }

    /**
     * Calculate total items in cart
     */
    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }

    /**
     * Calculate total amount of cart
     */
    public function getTotalAmountAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Get cart item by product ID
     */
    public function getItemByProduct(int $productId)
    {
        return $this->items()->where('product_id', $productId)->first();
    }

    /**
     * Add item to cart or update quantity if exists
     */
    public function addItem(int $productId, int $quantity = 1)
    {
        $existingItem = $this->getItemByProduct($productId);

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            return $existingItem->fresh();
        }

        return $this->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Update item quantity
     */
    public function updateItemQuantity(int $productId, int $quantity)
    {
        $item = $this->getItemByProduct($productId);
        
        if ($item) {
            if ($quantity <= 0) {
                $item->delete();
                return null;
            }
            
            $item->update(['quantity' => $quantity]);
            return $item->fresh();
        }

        return null;
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $productId): bool
    {
        $item = $this->getItemByProduct($productId);
        
        if ($item) {
            return $item->delete();
        }

        return false;
    }

    /**
     * Clear all items from cart
     */
    public function clearItems(): bool
    {
        return $this->items()->delete();
    }

    /**
     * Get cart summary
     */
    public function getSummary(): array
    {
        $items = $this->itemsWithProducts;
        
        return [
            'total_items' => $items->sum('quantity'),
            'total_unique_products' => $items->count(),
            'total_amount' => $items->sum(function ($item) {
                return $item->quantity * $item->product->price;
            }),
            'items_by_seller' => $items->groupBy('product.seller_id')->map(function ($sellerItems) {
                return [
                    'seller_name' => $sellerItems->first()->product->seller->name,
                    'items_count' => $sellerItems->count(),
                    'total_amount' => $sellerItems->sum(function ($item) {
                        return $item->quantity * $item->product->price;
                    }),
                ];
            })
        ];
    }
}