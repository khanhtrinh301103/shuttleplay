<?php
// File location: backend/app/Http/Requests/RegisterRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Cho phép tất cả request
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2'
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'confirmed', // Yêu cầu field password_confirmation
                Password::min(8) // Tối thiểu 8 ký tự
                    ->mixedCase() // Phải có chữ hoa và chữ thường
                    ->numbers() // Phải có số
                    ->symbols() // Phải có ký tự đặc biệt
                    ->uncompromised(), // Kiểm tra password không bị lộ
            ],
            'role' => [
                'nullable',
                'string',
                'in:customer,seller' // Chỉ cho phép customer hoặc seller
            ],
            'phone' => [
                'nullable', // later change to required if needed
                'string',
                'regex:/^([0-9\s\-\+\(\)]*)$/',
                'min:10',
                'max:20'
            ],
            'address' => [
                'nullable', // later change to required if needed
                'string',
                'max:500'
            ],
            'avatar_url' => [
                'nullable',
                'string',
                'url',
                'max:255'
            ],
            'birth_date' => [
                'nullable',
                'date',
                'before:today', // Ngày sinh phải trước hôm nay
                'after:1900-01-01' // Ngày sinh hợp lý
            ],
            'gender' => [
                'nullable',
                'string',
                'in:male,female,other'
            ],
            'bio' => [
                'nullable',
                'string',
                'max:1000' // Giới hạn bio 1000 ký tự
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên',
            'name.min' => 'Họ tên phải có ít nhất 2 ký tự',
            'name.max' => 'Họ tên không được vượt quá 255 ký tự',
            
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email này đã được sử dụng',
            
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            
            'role.in' => 'Vai trò chỉ có thể là customer hoặc seller',
            
            'phone.regex' => 'Số điện thoại không đúng định dạng',
            'phone.min' => 'Số điện thoại phải có ít nhất 10 số',
            'phone.max' => 'Số điện thoại không được vượt quá 20 số',
            
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự',
            
            'avatar_url.url' => 'URL avatar không đúng định dạng',
            'avatar_url.max' => 'URL avatar không được vượt quá 255 ký tự',
            
            'birth_date.date' => 'Ngày sinh không đúng định dạng',
            'birth_date.before' => 'Ngày sinh phải trước ngày hôm nay',
            'birth_date.after' => 'Ngày sinh không hợp lệ',
            
            'gender.in' => 'Giới tính chỉ có thể là male, female hoặc other',
            
            'bio.max' => 'Tiểu sử không được vượt quá 1000 ký tự',
        ];
    }
}