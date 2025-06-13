<?php
// File location: backend/tests/Feature/ProductTest.php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;

class ProductTest extends TestCase
{
    use WithFaker;

    protected $seller;
    protected $customer;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Tạo test data
        $this->seller = User::factory()->create([
            'role' => 'seller',
            'email' => 'seller_' . time() . '@test.com'
        ]);
        
        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'customer_' . time() . '@test.com'
        ]);
        
        $this->category = Category::create([
            'name' => 'Test Category ' . time(),
            'slug' => 'test-category-' . time()
        ]);
    }

    /**
     * Test seller can create product
     */
    public function test_seller_can_create_product()
    {
        echo "\n🚀 Testing seller can create product\n";
        
        $token = $this->seller->createToken('test-token')->plainTextToken;
        
        $productData = [
            'name' => 'Test Product ' . time(),
            'description' => 'This is a test product description',
            'price' => 99.99,
            'stock_qty' => 50,
            'category_id' => $this->category->id,
            'published' => true,
            'images' => [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg'
            ],
            'main_image_index' => 0
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/seller/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'product' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'stock_qty',
                        'category_id',
                        'seller_id',
                        'published',
                        'created_at',
                        'updated_at',
                        'category',
                        'images'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id
        ]);

        echo "✅ Seller can create product successfully!\n";
    }

    /**
     * Test customer cannot create product
     */
    public function test_customer_cannot_create_product()
    {
        echo "\n🚀 Testing customer cannot create product\n";
        
        $token = $this->customer->createToken('test-token')->plainTextToken;
        
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock_qty' => 50,
            'category_id' => $this->category->id,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/seller/products', $productData);

        $response->assertStatus(403);

        echo "✅ Customer correctly denied product creation!\n";
    }

    /**
     * Test seller can get their products
     */
    public function test_seller_can_get_their_products()
    {
        echo "\n🚀 Testing seller can get their products\n";
        
        // Tạo sản phẩm cho seller
        $product = Product::create([
            'name' => 'Seller Product ' . time(),
            'description' => 'Test description',
            'price' => 199.99,
            'stock_qty' => 10,
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'published' => true
        ]);

        $token = $this->seller->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/seller/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'products' => [
                        '*' => [
                            'id',
                            'name',
                            'price',
                            'stock_qty',
                            'category',
                            'images'
                        ]
                    ]
                ]
            ]);

        echo "✅ Seller can get their products successfully!\n";
    }

    /**
     * Test seller can update their product
     */
    public function test_seller_can_update_their_product()
    {
        echo "\n🚀 Testing seller can update their product\n";
        
        // Tạo sản phẩm cho seller
        $product = Product::create([
            'name' => 'Original Product',
            'description' => 'Original description',
            'price' => 99.99,
            'stock_qty' => 10,
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'published' => false
        ]);

        $token = $this->seller->createToken('test-token')->plainTextToken;
        
        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 149.99,
            'published' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/seller/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'product' => [
                        'name' => 'Updated Product Name',
                        'price' => '149.99',
                        'published' => true
                    ]
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 149.99,
            'published' => true
        ]);

        echo "✅ Seller can update their product successfully!\n";
    }

    /**
     * Test seller can delete their product
     */
    public function test_seller_can_delete_their_product()
    {
        echo "\n🚀 Testing seller can delete their product\n";
        
        // Tạo sản phẩm cho seller
        $product = Product::create([
            'name' => 'Product to Delete',
            'description' => 'Will be deleted',
            'price' => 99.99,
            'stock_qty' => 10,
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'published' => false
        ]);

        $token = $this->seller->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/seller/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công'
            ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);

        echo "✅ Seller can delete their product successfully!\n";
    }

    /**
     * Test seller can toggle product publish status
     */
    public function test_seller_can_toggle_product_publish_status()
    {
        echo "\n🚀 Testing seller can toggle product publish status\n";
        
        // Tạo sản phẩm chưa publish
        $product = Product::create([
            'name' => 'Product to Toggle',
            'description' => 'Toggle test',
            'price' => 99.99,
            'stock_qty' => 10,
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'published' => false
        ]);

        $token = $this->seller->createToken('test-token')->plainTextToken;

        // Toggle từ false -> true
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson("/api/seller/products/{$product->id}/toggle-publish");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'product' => [
                        'published' => true
                    ]
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'published' => true
        ]);

        echo "✅ Seller can toggle product publish status successfully!\n";
    }

    /**
     * Test get categories endpoint
     */
    public function test_can_get_categories()
    {
        echo "\n🚀 Testing can get categories\n";
        
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]
            ]);

        echo "✅ Can get categories successfully!\n";
    }
}