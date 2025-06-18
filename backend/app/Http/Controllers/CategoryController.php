<?php
// File location: backend/app/Http/Controllers/CategoryController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Lấy danh sách tất cả categories (public API)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Category::query();

            // Include product count if requested
            if ($request->get('include_counts', false)) {
                $query->withCount(['products as published_products_count' => function($q) {
                    $q->where('published', true)->where('stock_qty', '>', 0);
                }]);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            
            $allowedSortFields = ['name', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $categories = $query->get();

            // Format response
            $formattedCategories = $categories->map(function($category) use ($request) {
                $data = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'created_at' => $category->created_at->format('d/m/Y H:i'),
                    'updated_at' => $category->updated_at->format('d/m/Y H:i'),
                ];

                // Add product count if requested
                if ($request->get('include_counts', false)) {
                    $data['products_count'] = $category->published_products_count ?? 0;
                }

                return $data;
            });

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách danh mục thành công',
                'data' => [
                    'categories' => $formattedCategories,
                    'total' => $categories->count()
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
     * Lấy chi tiết một category theo slug (public API)
     * 
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug)
    {
        try {
            $category = Category::where('slug', $slug)->firstOrFail();

            // Get products count manually
            $productsCount = \App\Models\Product::where('category_id', $category->id)
                ->where('published', true)
                ->where('stock_qty', '>', 0)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Lấy chi tiết danh mục thành công',
                'data' => [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'products_count' => $productsCount,
                        'created_at' => $category->created_at->format('d/m/Y H:i'),
                        'updated_at' => $category->updated_at->format('d/m/Y H:i'),
                        'seo' => [
                            'title' => $category->name,
                            'description' => "Xem tất cả sản phẩm trong danh mục {$category->name}",
                            'url' => url("/categories/{$category->slug}")
                        ]
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Lấy danh sách categories có sản phẩm (for navigation/filtering)
     * 🔧 FIXED: Using direct query instead of relationship
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveCategories()
    {
        try {
            // Use direct whereHas with products table instead of publishedProducts relationship
            $categories = Category::whereHas('products', function($query) {
                $query->where('published', true)->where('stock_qty', '>', 0);
            })
            ->withCount(['products as published_products_count' => function($query) {
                $query->where('published', true)->where('stock_qty', '>', 0);
            }])
            ->orderBy('name', 'asc')
            ->get();

            $formattedCategories = $categories->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'products_count' => $category->published_products_count,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách danh mục có sản phẩm thành công',
                'data' => [
                    'active_categories' => $formattedCategories,
                    'total' => $categories->count()
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
}