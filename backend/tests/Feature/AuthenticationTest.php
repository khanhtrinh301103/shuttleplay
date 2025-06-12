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
     * Test user registration
     *
     * @return void
     */
    public function test_user_can_register()
    {
        $testEmail = 'test_' . time() . rand(1000, 9999) . '@example.com';

        echo "\n🚀 Testing registration with: $testEmail\n";

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => $testEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '0901234567',
            'address' => '123 Test Street'
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

        echo "✅ User created in Supabase: $testEmail\n";
    }

    /**
     * Test user login
     *
     * @return void
     */
    public function test_user_can_login()
    {
        $testEmail = 'test_login_' . time() . rand(1000, 9999) . '@example.com';
        
        echo "\n🚀 Testing login with: $testEmail\n";
        
        $user = User::factory()->create([
            'email' => $testEmail,
            'password' => bcrypt('Password123!')
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
                    'user',
                    'access_token',
                    'token_type'
                ]
            ]);

        echo "✅ Login successful!\n";
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
     * Test get authenticated user
     *
     * @return void
     */
    public function test_authenticated_user_can_get_profile()
    {
        $testEmail = 'test_profile_' . time() . rand(1000, 9999) . '@example.com';
        
        echo "\n🚀 Testing profile with: $testEmail\n";
        
        $user = User::factory()->create(['email' => $testEmail]);
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
                        'email' => $user->email
                    ]
                ]
            ]);

        echo "✅ Profile fetch successful!\n";
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
}