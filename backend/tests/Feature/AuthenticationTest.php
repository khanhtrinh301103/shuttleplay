<?php
// File location: backend/tests/Feature/AuthenticationTest.php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
// KHÔNG dùng DatabaseTransactions nữa - để data lưu thật
use Tests\TestCase;
use App\Models\User;

class AuthenticationTest extends TestCase
{
    // Chỉ dùng WithFaker, KHÔNG dùng DatabaseTransactions
    use WithFaker;

    /**
     * Test user registration as customer (default)
     *
     * @return void
     */
    public function test_user_can_register_as_customer()
    {
        $testEmail = 'test_customer_' . time() . rand(1000, 9999) . '@example.com';

        echo "\n🚀 Testing customer registration with: $testEmail\n";

        $response = $this->postJson('/api/register', [
            'name' => 'Test Customer',
            'email' => $testEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'customer', // Chỉ định role customer
            'phone' => '0901234567',
            'address' => '123 Test Street',
            'bio' => 'I am a test customer'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'phone',
                        'address',
                        'avatar_url',
                        'birth_date',
                        'gender',
                        'bio',
                        'created_at'
                    ],
                    'access_token',
                    'token_type'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $testEmail,
            'role' => 'customer'
        ]);

        echo "✅ Customer created in Supabase: $testEmail\n";
    }

    /**
     * Test user registration as seller
     *
     * @return void
     */
    public function test_user_can_register_as_seller()
    {
        $testEmail = 'test_seller_' . time() . rand(1000, 9999) . '@example.com';

        echo "\n🚀 Testing seller registration with: $testEmail\n";

        $response = $this->postJson('/api/register', [
            'name' => 'Test Seller',
            'email' => $testEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'seller', // Chỉ định role seller
            'phone' => '0901234568',
            'address' => '456 Seller Street',
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'bio' => 'I am a test seller'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'role' => 'seller',
                        'gender' => 'male'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $testEmail,
            'role' => 'seller',
            'gender' => 'male'
        ]);

        echo "✅ Seller created in Supabase: $testEmail\n";
    }

    /**
     * Test user registration without role (should default to customer)
     *
     * @return void
     */
    public function test_user_registration_defaults_to_customer()
    {
        $testEmail = 'test_default_' . time() . rand(1000, 9999) . '@example.com';

        echo "\n🚀 Testing default role with: $testEmail\n";

        $response = $this->postJson('/api/register', [
            'name' => 'Test Default User',
            'email' => $testEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '0901234569'
            // Không gửi role
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'role' => 'customer' // Should default to customer
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $testEmail,
            'role' => 'customer'
        ]);

        echo "✅ Default customer role assigned: $testEmail\n";
    }

    /**
     * Test user login with profile data
     *
     * @return void
     */
    public function test_user_can_login()
    {
        $testEmail = 'test_login_' . time() . rand(1000, 9999) . '@example.com';
        
        echo "\n🚀 Testing login with: $testEmail\n";
        
        $user = User::factory()->create([
            'email' => $testEmail,
            'password' => bcrypt('Password123!'),
            'role' => 'seller',
            'bio' => 'Test seller bio'
        ]);

        echo "👤 Created user ID: {$user->id}\n";

        $response = $this->postJson('/api/login', [
            'email' => $testEmail,
            'password' => 'Password123!'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'phone',
                        'address',
                        'avatar_url',
                        'birth_date',
                        'gender',
                        'bio',
                        'created_at'
                    ],
                    'access_token',
                    'token_type'
                ]
            ]);

        echo "✅ Login successful with profile data!\n";
    }

    /**
     * Test login with wrong credentials
     *
     * @return void
     */
    public function test_user_cannot_login_with_wrong_password()
    {
        $testEmail = 'test_wrong_' . time() . rand(1000, 9999) . '@example.com';
        
        echo "\n🚀 Testing wrong password with: $testEmail\n";
        
        $user = User::factory()->create([
            'email' => $testEmail,
            'password' => bcrypt('Password123!')
        ]);

        echo "👤 Created user ID: {$user->id}\n";

        $response = $this->postJson('/api/login', [
            'email' => $testEmail,
            'password' => 'WrongPassword'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);

        echo "✅ Wrong password correctly rejected!\n";
    }

    /**
     * Test get authenticated user profile with new fields
     *
     * @return void
     */
    public function test_authenticated_user_can_get_profile()
    {
        $testEmail = 'test_profile_' . time() . rand(1000, 9999) . '@example.com';
        
        echo "\n🚀 Testing profile with: $testEmail\n";
        
        $user = User::factory()->create([
            'email' => $testEmail,
            'role' => 'seller',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'gender' => 'female',
            'bio' => 'Test bio'
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        echo "👤 Created user ID: {$user->id}\n";

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'role' => 'seller',
                        'gender' => 'female',
                        'bio' => 'Test bio'
                    ]
                ]
            ]);

        echo "✅ Profile fetch successful with new fields!\n";
    }

    /**
     * Test logout
     *
     * @return void
     */
    public function test_user_can_logout()
    {
        $testEmail = 'test_logout_' . time() . rand(1000, 9999) . '@example.com';
        
        echo "\n🚀 Testing logout with: $testEmail\n";
        
        $user = User::factory()->create(['email' => $testEmail]);
        $token = $user->createToken('test-token')->plainTextToken;

        echo "👤 Created user ID: {$user->id}\n";

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Đăng xuất thành công'
            ]);

        echo "✅ Logout successful!\n";
    }

    /**
     * Test invalid role registration
     *
     * @return void
     */
    public function test_user_cannot_register_with_invalid_role()
    {
        $testEmail = 'test_invalid_role_' . time() . rand(1000, 9999) . '@example.com';

        echo "\n🚀 Testing invalid role registration with: $testEmail\n";

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => $testEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin' // Không cho phép đăng ký admin
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);

        echo "✅ Invalid role correctly rejected!\n";
    }
}