<?php
// File location: backend/app/Http/Requests/UpdateCartRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;
use App\Models\CartItem;

class UpdateCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Chỉ cho phép customer đã đăng nhập
        return auth()->check() && auth()->user()->role === 'customer';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:999'
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
            'quantity.required' => 'Vui lòng nhập số lượng',
            'quantity.integer' => 'Số lượng phải là số nguyên',
            'quantity.min' => 'Số lượng không được âm (dùng 0 để xóa sản phẩm)',
            'quantity.max' => 'Số lượng không được vượt quá 999',
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
            'quantity' => 'số lượng',
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
            // Lấy cart item ID từ route parameter
            $cartItemId = $this->route('cartItemId');
            
            if ($cartItemId) {
                $cartItem = CartItem::with('product')->find($cartItemId);
                
                if ($cartItem) {
                    $product = $cartItem->product;
                    $requestedQuantity = $this->input('quantity', 0);
                    
                    // Nếu quantity > 0, kiểm tra stock
                    if ($requestedQuantity > 0) {
                        // Kiểm tra sản phẩm vẫn còn publish
                        if (!$product->published) {
                            $validator->errors()->add(
                                'quantity',
                                'Sản phẩm này hiện không có sẵn'
                            );
                        }
                        
                        // Kiểm tra số lượng tồn kho
                        if ($product->stock_qty < $requestedQuantity) {
                            $validator->errors()->add(
                                'quantity',
                                "Chỉ còn {$product->stock_qty} sản phẩm trong kho"
                            );
                        }
                    }
                    
                    // Kiểm tra cart item có thuộc về user hiện tại không
                    if ($cartItem->cart->user_id !== auth()->id()) {
                        $validator->errors()->add(
                            'quantity',
                            'Bạn không có quyền cập nhật item này'
                        );
                    }
                } else {
                    $validator->errors()->add(
                        'quantity',
                        'Item không tồn tại trong giỏ hàng'
                    );
                }
            }
        });
    }
}