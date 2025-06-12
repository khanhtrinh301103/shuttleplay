<?php
// File location: backend/database/factories/UserFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'), // password mặc định cho test
            'role' => 'customer', // role mặc định
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the user is an admin.
     *
     * @return static
     */
    public function admin()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the user is a seller.
     *
     * @return static
     */
    public function seller()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'seller',
        ]);
    }

    /**
     * Indicate that the user is a customer.
     *
     * @return static
     */
    public function customer()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'customer',
        ]);
    }
}