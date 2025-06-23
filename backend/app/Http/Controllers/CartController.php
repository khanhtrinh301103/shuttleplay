<?php
// File location: backend/app/Http/Controllers/CartController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Lấy giỏ hàng của user hiện tại
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $cart = $this->cartService->getCartWithItems($user);

            return response()->json([
                'success' => true,
                'message' => 'Lấy giỏ hàng thành công',
                'data' => [
                    'cart' => new CartResource($cart)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     * 
     * @param AddToCartRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToCart(AddToCartRequest $request)
    {
        try {
            $user = Auth::user();
            $productId = $request->validated()['product_id'];
            $quantity = $request->validated()['quantity'];

            $result = $this->cartService->addToCart($user, $productId, $quantity);

            $message = $result['action'] === 'added' 
                ? 'Thêm sản phẩm vào giỏ hàng thành công'
                : 'Cập nhật số lượng sản phẩm trong giỏ hàng thành công';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'action' => $result['action'],
                    'cart' => new CartResource($result['cart'])
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thêm sản phẩm vào giỏ hàng thất bại',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cập nhật số lượng item trong giỏ hàng
     * 
     * @param UpdateCartRequest $request
     * @param int $cartItemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCartItem(UpdateCartRequest $request, int $cartItemId)
    {
        try {
            $user = Auth::user();
            $quantity = $request->validated()['quantity'];

            $result = $this->cartService->updateCartItem($user, $cartItemId, $quantity);

            $message = $result['action'] === 'removed' 
                ? 'Xóa sản phẩm khỏi giỏ hàng thành công'
                : 'Cập nhật số lượng sản phẩm thành công';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'action' => $result['action'],
                    'cart' => new CartResource($result['cart'])
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật giỏ hàng thất bại',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Xóa item khỏi giỏ hàng
     * 
     * @param int $cartItemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFromCart(int $cartItemId)
    {
        try {
            $user = Auth::user();
            $cart = $this->cartService->removeFromCart($user, $cartItemId);

            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm khỏi giỏ hàng thành công',
                'data' => [
                    'cart' => new CartResource($cart)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xóa sản phẩm khỏi giỏ hàng thất bại',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Xóa tất cả items trong giỏ hàng
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCart()
    {
        try {
            $user = Auth::user();
            $cart = $this->cartService->clearCart($user);

            return response()->json([
                'success' => true,
                'message' => 'Xóa tất cả sản phẩm trong giỏ hàng thành công',
                'data' => [
                    'cart' => new CartResource($cart)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xóa giỏ hàng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy số lượng items trong giỏ hàng (quick check)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCartCount()
    {
        try {
            $user = Auth::user();
            $cart = $this->cartService->getOrCreateCart($user);
            $totalItems = $cart->items()->sum('quantity');

            return response()->json([
                'success' => true,
                'message' => 'Lấy số lượng sản phẩm trong giỏ hàng thành công',
                'data' => [
                    'total_items' => $totalItems,
                    'cart_id' => $cart->id
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy số lượng sản phẩm trong giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate và clean up giỏ hàng (xóa items không available)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateCart()
    {
        try {
            $user = Auth::user();
            $result = $this->cartService->validateAndCleanCart($user);

            $message = $result['has_changes'] 
                ? 'Giỏ hàng đã được cập nhật do thay đổi tình trạng sản phẩm'
                : 'Giỏ hàng của bạn đã được kiểm tra và không có thay đổi';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'cart' => new CartResource($result['cart']),
                    'changes' => [
                        'has_changes' => $result['has_changes'],
                        'removed_items' => $result['removed_items'],
                        'updated_items' => $result['updated_items']
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi validate giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê giỏ hàng
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCartStats()
    {
        try {
            $user = Auth::user();
            $stats = $this->cartService->getCartStats($user);

            return response()->json([
                'success' => true,
                'message' => 'Lấy thống kê giỏ hàng thành công',
                'data' => [
                    'stats' => [
                        'total_items' => $stats['total_items'],
                        'total_unique_products' => $stats['total_unique_products'],
                        'total_amount' => [
                            'raw' => $stats['total_amount'],
                            'formatted' => number_format($stats['total_amount'], 0, ',', '.') . ' VND',
                            'currency' => 'VND'
                        ],
                        'sellers_count' => $stats['sellers_count'],
                        'categories_count' => $stats['categories_count'],
                        'average_item_price' => [
                            'raw' => round($stats['average_item_price'], 2),
                            'formatted' => number_format($stats['average_item_price'], 0, ',', '.') . ' VND',
                            'currency' => 'VND'
                        ],
                        'price_range' => [
                            'most_expensive' => [
                                'raw' => $stats['most_expensive_item'],
                                'formatted' => number_format($stats['most_expensive_item'] ?? 0, 0, ',', '.') . ' VND'
                            ],
                            'cheapest' => [
                                'raw' => $stats['cheapest_item'],
                                'formatted' => number_format($stats['cheapest_item'] ?? 0, 0, ',', '.') . ' VND'
                            ]
                        ]
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Merge guest cart với user cart (dùng khi user login)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mergeGuestCart(Request $request)
    {
        try {
            $request->validate([
                'guest_cart_items' => 'required|array',
                'guest_cart_items.*.product_id' => 'required|integer|exists:products,id',
                'guest_cart_items.*.quantity' => 'required|integer|min:1|max:999'
            ]);

            $user = Auth::user();
            $guestCartItems = $request->input('guest_cart_items');

            $result = $this->cartService->mergeGuestCart($user, $guestCartItems);

            return response()->json([
                'success' => true,
                'message' => "Đã merge {$result['merged_count']}/{$result['total_guest_items']} sản phẩm từ guest cart",
                'data' => [
                    'cart' => new CartResource($result['cart']),
                    'merge_summary' => [
                        'merged_count' => $result['merged_count'],
                        'total_guest_items' => $result['total_guest_items'],
                        'errors' => $result['errors']
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi merge guest cart',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}