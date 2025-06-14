<?php
// File location: backend/app/Http/Requests/CreateProductRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Chỉ cho phép seller tạo sản phẩm
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
                'required',
                'string',
                'max:255',
                'min:3'
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99'
            ],
            'stock_qty' => [
                'required',
                'integer',
                'min:0',
                'max:999999'
            ],
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id'
            ],
            'published' => [
                'sometimes',
                'boolean'
            ],
            // Note: Images are now handled separately via ProductImageController
            // This keeps product creation and image upload as separate concerns
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
            'name.required' => 'Vui lòng nhập tên sản phẩm',
            'name.min' => 'Tên sản phẩm phải có ít nhất 3 ký tự',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự',
            
            'description.max' => 'Mô tả sản phẩm không được vượt quá 5000 ký tự',
            
            'price.required' => 'Vui lòng nhập giá sản phẩm',
            'price.numeric' => 'Giá sản phẩm phải là số',
            'price.min' => 'Giá sản phẩm không được âm',
            'price.max' => 'Giá sản phẩm quá lớn',
            
            'stock_qty.required' => 'Vui lòng nhập số lượng tồn kho',
            'stock_qty.integer' => 'Số lượng tồn kho phải là số nguyên',
            'stock_qty.min' => 'Số lượng tồn kho không được âm',
            'stock_qty.max' => 'Số lượng tồn kho quá lớn',
            
            'category_id.required' => 'Vui lòng chọn danh mục sản phẩm',
            'category_id.exists' => 'Danh mục sản phẩm không tồn tại',
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
        ];
    }
}