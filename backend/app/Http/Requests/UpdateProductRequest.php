<?php
// File location: backend/app/Http/Requests/UpdateProductRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Chỉ cho phép seller cập nhật sản phẩm
        return auth()->check() && auth()->user()->role === 'seller';
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
                'sometimes',
                'string',
                'max:255',
                'min:3'
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000'
            ],
            'price' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:999999999.99'
            ],
            'stock_qty' => [
                'sometimes',
                'integer',
                'min:0',
                'max:999999'
            ],
            'category_id' => [
                'sometimes',
                'integer',
                'exists:categories,id'
            ],
            'published' => [
                'sometimes',
                'boolean'
            ],
            // Xử lý images - có thể là array của URL hoặc file uploads
            'images' => [
                'sometimes',
                'nullable',
                'array',
                'max:10' // Tối đa 10 ảnh
            ],
            'images.*' => [
                'string',
                'max:255'
            ],
            // Đánh dấu ảnh nào là ảnh chính
            'main_image_index' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0'
            ]
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
            'name.min' => 'Tên sản phẩm phải có ít nhất 3 ký tự',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự',
            
            'description.max' => 'Mô tả sản phẩm không được vượt quá 5000 ký tự',
            
            'price.numeric' => 'Giá sản phẩm phải là số',
            'price.min' => 'Giá sản phẩm không được âm',
            'price.max' => 'Giá sản phẩm quá lớn',
            
            'stock_qty.integer' => 'Số lượng tồn kho phải là số nguyên',
            'stock_qty.min' => 'Số lượng tồn kho không được âm',
            'stock_qty.max' => 'Số lượng tồn kho quá lớn',
            
            'category_id.exists' => 'Danh mục sản phẩm không tồn tại',
            
            'images.array' => 'Danh sách hình ảnh không hợp lệ',
            'images.max' => 'Tối đa 10 hình ảnh cho mỗi sản phẩm',
            'images.*.string' => 'URL hình ảnh phải là chuỗi',
            'images.*.max' => 'URL hình ảnh quá dài',
            
            'main_image_index.integer' => 'Chỉ số ảnh chính phải là số nguyên',
            'main_image_index.min' => 'Chỉ số ảnh chính không hợp lệ',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'tên sản phẩm',
            'description' => 'mô tả',
            'price' => 'giá',
            'stock_qty' => 'số lượng tồn kho',
            'category_id' => 'danh mục',
            'images' => 'hình ảnh',
            'main_image_index' => 'ảnh chính'
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Kiểm tra main_image_index có hợp lệ với số lượng images không
            if ($this->has('images') && $this->has('main_image_index')) {
                $images = $this->input('images', []);
                $mainImageIndex = $this->input('main_image_index');
                
                if ($mainImageIndex !== null && $mainImageIndex >= count($images)) {
                    $validator->errors()->add(
                        'main_image_index', 
                        'Chỉ số ảnh chính vượt quá số lượng ảnh có sẵn'
                    );
                }
            }
        });
    }
}