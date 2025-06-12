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
            'phone' => [
                'nullable',
                'string',
                'regex:/^([0-9\s\-\+\(\)]*)$/',
                'min:10',
                'max:20'
            ],
            'address' => [
                'nullable',
                'string',
                'max:500'
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
            
            'phone.regex' => 'Số điện thoại không đúng định dạng',
            'phone.min' => 'Số điện thoại phải có ít nhất 10 số',
            'phone.max' => 'Số điện thoại không được vượt quá 20 số',
            
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự',
        ];
    }
}