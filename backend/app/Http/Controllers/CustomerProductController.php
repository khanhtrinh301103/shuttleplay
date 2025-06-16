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
     * Lấy danh sách tất cả sản phẩm đã công bố cho customers
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with(['category', 'seller', 'mainImage'])
                ->published() // Chỉ lấy sản phẩm đã publish
                ->inStock(); // Chỉ lấy sản phẩm còn hàng

            // Filter by category
            if ($request->has('category_id') && !empty($request->category_id)) {
                $query->byCategory($request->category_id);
            }

            // Search by product name
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'ILIKE', '%' . $request->search . '%');
            }

            // Filter by price range
            if ($request->has('min_price') && !empty($request->min_price)) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price') && !empty($request->max_price)) {
                $query->where('price', '<=', $request->max_price);
            }

            // Filter by seller
            if ($request->has('seller_id') && !empty($request->seller_id)) {
                $query->bySeller($request->seller_id);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSortFields = ['created_at', 'updated_at', 'name', 'price'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 12), 50); // Max 50 items per page
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
     * Lấy chi tiết một sản phẩm cho customer
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $product = Product::with([
                'category', 
                'seller:id,name,email', // Chỉ lấy một số thông tin cần thiết của seller
                'images' => function($query) {
                    $query->orderBy('is_main', 'desc')->orderBy('created_at', 'asc');
                }
            ])
            ->published() // Chỉ cho phép xem sản phẩm đã publish
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
                'message' => 'Không tìm thấy sản phẩm hoặc sản phẩm chưa được công bố',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Lấy danh sách sản phẩm theo category
     * 
     * @param int $categoryId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCategory($categoryId, Request $request)
    {
        try {
            // Kiểm tra category có tồn tại không
            $category = Category::findOrFail($categoryId);

            $query = Product::with(['category', 'seller', 'mainImage'])
                ->published()
                ->inStock()
                ->byCategory($categoryId);

            // Apply additional filters
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'ILIKE', '%' . $request->search . '%');
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
            
            $allowedSortFields = ['created_at', 'updated_at', 'name', 'price'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 12), 50);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => "Lấy danh sách sản phẩm trong danh mục '{$category->name}' thành công",
                'data' => [
                    'category' => $category,
                    'products' => CustomerProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục hoặc lỗi khi lấy sản phẩm',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Lấy danh sách sản phẩm từ một seller cụ thể
     * 
     * @param int $sellerId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySeller($sellerId, Request $request)
    {
        try {
            // Kiểm tra seller có tồn tại không
            $seller = \App\Models\User::where('id', $sellerId)
                ->where('role', 'seller')
                ->firstOrFail();

            $query = Product::with(['category', 'seller', 'mainImage'])
                ->published()
                ->inStock()
                ->bySeller($sellerId);

            // Apply filters
            if ($request->has('category_id') && !empty($request->category_id)) {
                $query->byCategory($request->category_id);
            }

            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'ILIKE', '%' . $request->search . '%');
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
            
            $allowedSortFields = ['created_at', 'updated_at', 'name', 'price'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 12), 50);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => "Lấy danh sách sản phẩm từ seller '{$seller->name}' thành công",
                'data' => [
                    'seller' => [
                        'id' => $seller->id,
                        'name' => $seller->name,
                    ],
                    'products' => CustomerProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy seller hoặc lỗi khi lấy sản phẩm',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Lấy danh sách categories cho filter dropdown
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        try {
            $categories = Category::orderBy('name', 'asc')->get(['id', 'name', 'slug']);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách danh mục thành công',
                'data' => [
                    'categories' => $categories
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tìm kiếm sản phẩm theo từ khóa
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'q' => 'required|string|min:1|max:255',
                'category_id' => 'sometimes|integer|exists:categories,id',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|min:0',
                'per_page' => 'sometimes|integer|min:1|max:50'
            ]);

            $searchTerm = $request->get('q');
            
            $query = Product::with(['category', 'seller', 'mainImage'])
                ->published()
                ->inStock()
                ->where(function($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'ILIKE', '%' . $searchTerm . '%')
                             ->orWhere('description', 'ILIKE', '%' . $searchTerm . '%');
                });

            // Apply filters
            if ($request->has('category_id') && !empty($request->category_id)) {
                $query->byCategory($request->category_id);
            }

            if ($request->has('min_price') && !empty($request->min_price)) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price') && !empty($request->max_price)) {
                $query->where('price', '<=', $request->max_price);
            }

            // Sorting - prioritize name relevance for search
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            
            $allowedSortFields = ['created_at', 'updated_at', 'name', 'price'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 12), 50);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => "Tìm kiếm sản phẩm với từ khóa '{$searchTerm}' thành công",
                'data' => [
                    'search_term' => $searchTerm,
                    'products' => CustomerProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
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