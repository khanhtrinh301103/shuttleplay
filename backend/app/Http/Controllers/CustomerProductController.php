<?php
// File location: backend/app/Http/Controllers/CustomerProductController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerProductResource;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class CustomerProductController extends Controller
{
    /**
     * Lấy danh sách tất cả sản phẩm đã publish (public API)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with(['category', 'images', 'seller'])
                ->published() // Chỉ lấy sản phẩm đã publish
                ->inStock(); // Chỉ lấy sản phẩm còn hàng

            // Search by name
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where('name', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'ILIKE', "%{$searchTerm}%");
            }

            // Filter by category
            if ($request->has('category_id') && !empty($request->category_id)) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by price range
            if ($request->has('min_price') && !empty($request->min_price)) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price') && !empty($request->max_price)) {
                $query->where('price', '<=', $request->max_price);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSortFields = ['created_at', 'updated_at', 'name', 'price'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 12);
            $perPage = min($perPage, 50); // Giới hạn tối đa 50 items per page

            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm thành công',
                'data' => [
                    'products' => CustomerProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                        'has_more_pages' => $products->hasMorePages(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết một sản phẩm (public API)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $product = Product::with(['category', 'images', 'seller', 'reviews.user'])
                ->published()
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Lấy chi tiết sản phẩm thành công',
                'data' => [
                    'product' => new CustomerProductResource($product)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Lấy sản phẩm theo category slug
     * 
     * @param string $categorySlug
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCategory($categorySlug, Request $request)
    {
        try {
            $category = Category::where('slug', $categorySlug)->firstOrFail();

            $query = Product::with(['category', 'images', 'seller'])
                ->published()
                ->inStock()
                ->where('category_id', $category->id);

            // Search trong category
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'ILIKE', "%{$searchTerm}%");
                });
            }

            // Filter by price range
            if ($request->has('min_price') && !empty($request->min_price)) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price') && !empty($request->max_price)) {
                $query->where('price', '<=', $request->max_price);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSortFields = ['created_at', 'updated_at', 'name', 'price'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 12);
            $perPage = min($perPage, 50);

            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => "Lấy sản phẩm trong danh mục '{$category->name}' thành công",
                'data' => [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ],
                    'products' => CustomerProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                        'has_more_pages' => $products->hasMorePages(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục sản phẩm',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Lấy sản phẩm liên quan (cùng category, trừ sản phẩm hiện tại)
     * 
     * @param int $productId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRelatedProducts($productId, Request $request)
    {
        try {
            $currentProduct = Product::findOrFail($productId);
            
            $limit = $request->get('limit', 8);
            $limit = min($limit, 20); // Giới hạn tối đa 20 sản phẩm liên quan

            $relatedProducts = Product::with(['category', 'images', 'seller'])
                ->published()
                ->inStock()
                ->where('category_id', $currentProduct->category_id)
                ->where('id', '!=', $productId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy sản phẩm liên quan thành công',
                'data' => [
                    'related_products' => CustomerProductResource::collection($relatedProducts),
                    'total' => $relatedProducts->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy sản phẩm liên quan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy sản phẩm mới nhất
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatestProducts(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $limit = min($limit, 20); // Giới hạn tối đa 20 sản phẩm

            $latestProducts = Product::with(['category', 'images', 'seller'])
                ->published()
                ->inStock()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy sản phẩm mới nhất thành công',
                'data' => [
                    'latest_products' => CustomerProductResource::collection($latestProducts),
                    'total' => $latestProducts->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy sản phẩm mới nhất',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy sản phẩm theo seller
     * 
     * @param int $sellerId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySeller($sellerId, Request $request)
    {
        try {
            $query = Product::with(['category', 'images', 'seller'])
                ->published()
                ->inStock()
                ->where('seller_id', $sellerId);

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSortFields = ['created_at', 'updated_at', 'name', 'price'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 12);
            $perPage = min($perPage, 50);

            $products = $query->paginate($perPage);

            // Lấy thông tin seller
            $seller = \App\Models\User::findOrFail($sellerId);

            return response()->json([
                'success' => true,
                'message' => "Lấy sản phẩm của seller '{$seller->name}' thành công",
                'data' => [
                    'seller' => [
                        'id' => $seller->id,
                        'name' => $seller->name,
                        'email' => $seller->email,
                    ],
                    'products' => CustomerProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                        'has_more_pages' => $products->hasMorePages(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy sản phẩm của seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search sản phẩm nâng cao
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProducts(Request $request)
    {
        try {
            $query = Product::with(['category', 'images', 'seller'])
                ->published()
                ->inStock();

            // Validate search query
            $request->validate([
                'q' => 'required|string|min:2|max:255',
                'category_id' => 'sometimes|integer|exists:categories,id',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|min:0',
                'sort_by' => 'sometimes|in:created_at,updated_at,name,price',
                'sort_order' => 'sometimes|in:asc,desc',
                'per_page' => 'sometimes|integer|min:1|max:50'
            ]);

            $searchTerm = $request->q;

            // Search trong name và description
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'ILIKE', "%{$searchTerm}%");
            });

            // Additional filters
            if ($request->has('category_id') && !empty($request->category_id)) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('min_price') && !empty($request->min_price)) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price') && !empty($request->max_price)) {
                $query->where('price', '<=', $request->max_price);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 12);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => "Tìm kiếm sản phẩm cho '{$searchTerm}' thành công",
                'data' => [
                    'search_query' => $searchTerm,
                    'applied_filters' => [
                        'category_id' => $request->category_id,
                        'min_price' => $request->min_price,
                        'max_price' => $request->max_price,
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder,
                    ],
                    'products' => CustomerProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                        'has_more_pages' => $products->hasMorePages(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tìm kiếm sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}