<?php
// File location: backend/app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Tên bảng trong database
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Các trường cho phép mass-assign.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',      // customer, seller, admin
        'phone',     // số điện thoại
        'address',   // địa chỉ
    ];

    /**
     * Các trường ẩn khi xuất ra mảng/JSON.
     *
     * @var array<int,string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Các trường cast kiểu dữ liệu.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Giá trị mặc định cho các trường
     *
     * @var array
     */
    protected $attributes = [
        'role' => 'customer', // Role mặc định khi đăng ký
    ];

    /**
     * Kiểm tra xem user có phải là admin không
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Kiểm tra xem user có phải là seller không
     *
     * @return bool
     */
    public function isSeller()
    {
        return $this->role === 'seller';
    }

    /**
     * Kiểm tra xem user có phải là customer không
     *
     * @return bool
     */
    public function isCustomer()
    {
        return $this->role === 'customer';
    }

    /**
     * Relationship với bảng products (seller)
     */
    public function products()
    {
        return $this->hasMany(\App\Models\Product::class, 'seller_id');
    }

    /**
     * Relationship với bảng orders (customer)
     */
    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'user_id');
    }

    /**
     * Relationship với bảng shopping_carts
     */
    public function shoppingCart()
    {
        return $this->hasOne(\App\Models\ShoppingCart::class, 'user_id');
    }

    /**
     * Relationship với bảng user_addresses
     */
    public function addresses()
    {
        return $this->hasMany(\App\Models\UserAddress::class, 'user_id');
    }
}