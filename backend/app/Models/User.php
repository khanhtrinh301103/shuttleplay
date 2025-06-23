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
        'role',         // customer, seller, admin
        'phone',        // số điện thoại
        'address',      // địa chỉ
        'avatar_url',   // URL ảnh avatar
        'birth_date',   // ngày sinh
        'gender',       // giới tính
        'bio',          // tiểu sử ngắn
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
        'birth_date' => 'date',
    ];

    /**
     * Giá trị mặc định cho các trường
     *
     * @var array
     */
    protected $attributes = [
        'role' => 'customer', // Role mặc định khi đăng ký (nhưng có thể override)
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
     * 🆕 Relationship với bảng shopping_carts (customer)
     */
    public function cart()
    {
        return $this->hasOne(\App\Models\Cart::class, 'user_id');
    }

    /**
     * 🆕 Relationship với cart items thông qua cart (customer)
     */
    public function cartItems()
    {
        return $this->hasManyThrough(
            \App\Models\CartItem::class,
            \App\Models\Cart::class,
            'user_id', // Foreign key on carts table
            'cart_id', // Foreign key on cart_items table
            'id',      // Local key on users table
            'id'       // Local key on carts table
        );
    }

    /**
     * Relationship với bảng user_addresses
     */
    public function addresses()
    {
        return $this->hasMany(\App\Models\UserAddress::class, 'user_id');
    }

    /**
     * 🆕 Get or create cart for this user
     */
    public function getOrCreateCart()
    {
        if (!$this->cart) {
            $this->cart()->create();
        }
        
        return $this->cart;
    }

    /**
     * 🆕 Get total items in user's cart
     */
    public function getCartItemsCountAttribute()
    {
        return $this->cart ? $this->cart->items()->sum('quantity') : 0;
    }

    /**
     * 🆕 Get total amount in user's cart
     */
    public function getCartTotalAmountAttribute()
    {
        if (!$this->cart) {
            return 0;
        }

        return $this->cart->items()->with('product')->get()->sum(function($item) {
            return $item->quantity * ($item->product ? $item->product->price : 0);
        });
    }
}