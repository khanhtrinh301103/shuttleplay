<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Các trường cho phép mass-assign.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',      // thêm nếu bạn muốn assign role ngay khi tạo
        'phone',     // thêm nếu cần lưu số điện thoại
        'address',   // thêm nếu cần lưu địa chỉ
    ];

    /**
     * Các trường ẩn khi xuất ra mảng/JSON.
     *
     * @var array<int,string>
     */
    protected $hidden = [
        'password',
        // 'remember_token', // bỏ nếu không dùng cột này
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
}
