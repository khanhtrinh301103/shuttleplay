<?php
// File location: backend/app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Category;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Lấy danh sách sản phẩm của seller hiện tại
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Chỉ seller mới có thể xem sản phẩm của mình
            if ($user->role !== 'seller') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ seller mới có thể xem danh sách sản phẩm'
                ], 403);
            }

            $products = Product::with(['category', 'images'])
                ->where('seller_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm thành công',
                'data' => [
                    'products' => $products
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
     * Tạo sản phẩm mới
     * 
     * @param CreateProductRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateProductRequest $request)
    {
        try {
            $user = Auth::user();
            
            // Chỉ seller mới có thể tạo sản phẩm
            if ($user->role !== 'seller') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ seller mới có thể tạo sản phẩm'
                ], 403);
            }

            $product = $this->productService->createProduct($request->validated(), $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Tạo sản phẩm thành công',
                'data' => [
                    'product' => $product->load(['category', 'images'])
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo sản phẩm thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết một sản phẩm
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $product = Product::with(['category', 'images', 'seller'])->findOrFail($id);

            // Chỉ seller sở hữu sản phẩm mới có thể xem chi tiết
            if ($user->role === 'seller' && $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem sản phẩm này'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy chi tiết sản phẩm thành công',
                'data' => [
                    'product' => $product
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
     * Cập nhật sản phẩm
     * 
     * @param UpdateProductRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $user = Auth::user();
            $product = Product::findOrFail($id);

            // Chỉ seller sở hữu sản phẩm mới có thể cập nhật
            if ($user->role !== 'seller' || $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật sản phẩm này'
                ], 403);
            }

            $updatedProduct = $this->productService->updateProduct($product, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công',
                'data' => [
                    'product' => $updatedProduct->load(['category', 'images'])
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật sản phẩm thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa sản phẩm
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $product = Product::findOrFail($id);

            // Chỉ seller sở hữu sản phẩm mới có thể xóa
            if ($user->role !== 'seller' || $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa sản phẩm này'
                ], 403);
            }

            $this->productService->deleteProduct($product);

            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xóa sản phẩm thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle trạng thái published của sản phẩm
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function togglePublish($id)
    {
        try {
            $user = Auth::user();
            $product = Product::findOrFail($id);

            // Chỉ seller sở hữu sản phẩm mới có thể thay đổi trạng thái
            if ($user->role !== 'seller' || $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thay đổi trạng thái sản phẩm này'
                ], 403);
            }

            $product->published = !$product->published;
            $product->save();

            $status = $product->published ? 'công bố' : 'ẩn';

            return response()->json([
                'success' => true,
                'message' => "Đã {$status} sản phẩm thành công",
                'data' => [
                    'product' => $product->load(['category', 'images'])
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thay đổi trạng thái sản phẩm thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách categories để seller chọn khi tạo sản phẩm
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        try {
            $categories = Category::orderBy('name', 'asc')->get();

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
}