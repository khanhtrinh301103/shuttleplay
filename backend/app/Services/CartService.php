<?php
// File location: backend/app/Services/CartService.php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Lấy hoặc tạo cart cho user
     *
     * @param User $user
     * @return Cart
     */
    public function getOrCreateCart(User $user): Cart
    {
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id
            ]);
        }

        return $cart;
    }

    /**
     * Lấy cart với tất cả items và product details
     *
     * @param User $user
     * @return Cart
     */
    public function getCartWithItems(User $user): Cart
    {
        $cart = $this->getOrCreateCart($user);
        $cart->load(['itemsWithProducts']);
        
        return $cart;
    }

    /**
     * Thêm sản phẩm vào cart
     *
     * @param User $user
     * @param int $productId
     * @param int $quantity
     * @return array
     */
    public function addToCart(User $user, int $productId, int $quantity = 1): array
    {
        return DB::transaction(function () use ($user, $productId, $quantity) {
            $product = Product::findOrFail($productId);
            
            // Kiểm tra sản phẩm có available không
            if (!$product->published) {
                throw new \Exception('Sản phẩm này hiện không có sẵn');
            }

            // Kiểm tra stock
            if ($product->stock_qty < $quantity) {
                throw new \Exception("Chỉ còn {$product->stock_qty} sản phẩm trong kho");
            }

            // Kiểm tra không thể mua sản phẩm của chính mình
            if ($product->seller_id === $user->id) {
                throw new \Exception('Bạn không thể mua sản phẩm của chính mình');
            }

            $cart = $this->getOrCreateCart($user);
            $existingItem = $cart->getItemByProduct($productId);

            if ($existingItem) {
                // Kiểm tra tổng số lượng mới có vượt quá stock không
                $newQuantity = $existingItem->quantity + $quantity;
                if ($newQuantity > $product->stock_qty) {
                    throw new \Exception("Tổng số lượng ({$newQuantity}) vượt quá số lượng trong kho ({$product->stock_qty})");
                }

                $existingItem->increment('quantity', $quantity);
                $cartItem = $existingItem->fresh();
                $action = 'updated';
            } else {
                $cartItem = $cart->addItem($productId, $quantity);
                $action = 'added';
            }

            // Load product details
            $cartItem->load(['product', 'product.images', 'product.seller']);

            return [
                'action' => $action,
                'cart_item' => $cartItem,
                'cart' => $cart->fresh(['itemsWithProducts'])
            ];
        });
    }

    /**
     * Cập nhật số lượng item trong cart
     *
     * @param User $user
     * @param int $cartItemId
     * @param int $quantity
     * @return array
     */
    public function updateCartItem(User $user, int $cartItemId, int $quantity): array
    {
        return DB::transaction(function () use ($user, $cartItemId, $quantity) {
            $cartItem = CartItem::with(['cart', 'product'])->findOrFail($cartItemId);
            
            // Kiểm tra cart item có thuộc về user không
            if ($cartItem->cart->user_id !== $user->id) {
                throw new \Exception('Bạn không có quyền cập nhật item này');
            }

            // Nếu quantity = 0, xóa item
            if ($quantity <= 0) {
                $cartItem->delete();
                return [
                    'action' => 'removed',
                    'cart_item' => null,
                    'cart' => $cartItem->cart->fresh(['itemsWithProducts'])
                ];
            }

            $product = $cartItem->product;

            // Kiểm tra sản phẩm vẫn available
            if (!$product->published) {
                throw new \Exception('Sản phẩm này hiện không có sẵn');
            }

            // Kiểm tra stock
            if ($product->stock_qty < $quantity) {
                throw new \Exception("Chỉ còn {$product->stock_qty} sản phẩm trong kho");
            }

            $cartItem->update(['quantity' => $quantity]);
            $cartItem->load(['product', 'product.images', 'product.seller']);

            return [
                'action' => 'updated',
                'cart_item' => $cartItem,
                'cart' => $cartItem->cart->fresh(['itemsWithProducts'])
            ];
        });
    }

    /**
     * Xóa item khỏi cart
     *
     * @param User $user
     * @param int $cartItemId
     * @return Cart
     */
    public function removeFromCart(User $user, int $cartItemId): Cart
    {
        return DB::transaction(function () use ($user, $cartItemId) {
            $cartItem = CartItem::with('cart')->findOrFail($cartItemId);
            
            // Kiểm tra cart item có thuộc về user không
            if ($cartItem->cart->user_id !== $user->id) {
                throw new \Exception('Bạn không có quyền xóa item này');
            }

            $cart = $cartItem->cart;
            $cartItem->delete();

            return $cart->fresh(['itemsWithProducts']);
        });
    }

    /**
     * Xóa tất cả items trong cart
     *
     * @param User $user
     * @return Cart
     */
    public function clearCart(User $user): Cart
    {
        return DB::transaction(function () use ($user) {
            $cart = $this->getOrCreateCart($user);
            $cart->clearItems();

            return $cart->fresh(['itemsWithProducts']);
        });
    }

    /**
     * Kiểm tra và clean up các items không available trong cart
     *
     * @param User $user
     * @return array
     */
    public function validateAndCleanCart(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $cart = $this->getCartWithItems($user);
            $removedItems = [];
            $updatedItems = [];

            foreach ($cart->itemsWithProducts as $item) {
                $product = $item->product;

                // Xóa items của sản phẩm đã bị unpublish hoặc xóa
                if (!$product || !$product->published) {
                    $removedItems[] = [
                        'item_id' => $item->id,
                        'product_name' => $item->product_name,
                        'reason' => 'Sản phẩm không còn có sẵn'
                    ];
                    $item->delete();
                    continue;
                }

                // Cập nhật quantity nếu vượt quá stock
                if ($item->quantity > $product->stock_qty) {
                    if ($product->stock_qty > 0) {
                        $oldQuantity = $item->quantity;
                        $item->update(['quantity' => $product->stock_qty]);
                        $updatedItems[] = [
                            'item_id' => $item->id,
                            'product_name' => $product->name,
                            'old_quantity' => $oldQuantity,
                            'new_quantity' => $product->stock_qty,
                            'reason' => 'Điều chỉnh số lượng theo tồn kho'
                        ];
                    } else {
                        $removedItems[] = [
                            'item_id' => $item->id,
                            'product_name' => $product->name,
                            'reason' => 'Sản phẩm đã hết hàng'
                        ];
                        $item->delete();
                    }
                }
            }

            return [
                'cart' => $cart->fresh(['itemsWithProducts']),
                'removed_items' => $removedItems,
                'updated_items' => $updatedItems,
                'has_changes' => !empty($removedItems) || !empty($updatedItems)
            ];
        });
    }

    /**
     * Lấy thống kê cart của user
     *
     * @param User $user
     * @return array
     */
    public function getCartStats(User $user): array
    {
        $cart = $this->getCartWithItems($user);
        
        $stats = [
            'total_items' => $cart->total_items,
            'total_unique_products' => $cart->itemsWithProducts->count(),
            'total_amount' => $cart->total_amount,
            'sellers_count' => $cart->itemsWithProducts->pluck('product.seller_id')->unique()->count(),
            'categories_count' => $cart->itemsWithProducts->pluck('product.category_id')->unique()->count(),
            'average_item_price' => $cart->itemsWithProducts->count() > 0 
                ? $cart->total_amount / $cart->total_items 
                : 0,
            'most_expensive_item' => $cart->itemsWithProducts->max('product.price'),
            'cheapest_item' => $cart->itemsWithProducts->min('product.price'),
        ];

        return $stats;
    }

    /**
     * Merge cart từ guest user (nếu có) với cart của logged user
     * Useful cho trường hợp user add to cart khi chưa login, sau đó login
     *
     * @param User $user
     * @param array $guestCartItems
     * @return Cart
     */
    public function mergeGuestCart(User $user, array $guestCartItems): Cart
    {
        return DB::transaction(function () use ($user, $guestCartItems) {
            $cart = $this->getOrCreateCart($user);
            $mergedCount = 0;
            $errors = [];

            foreach ($guestCartItems as $guestItem) {
                try {
                    $productId = $guestItem['product_id'];
                    $quantity = $guestItem['quantity'];
                    
                    $this->addToCart($user, $productId, $quantity);
                    $mergedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'product_id' => $guestItem['product_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            return [
                'cart' => $cart->fresh(['itemsWithProducts']),
                'merged_count' => $mergedCount,
                'total_guest_items' => count($guestCartItems),
                'errors' => $errors
            ];
        });
    }
}