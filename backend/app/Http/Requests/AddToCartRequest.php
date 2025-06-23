<?php
// File location: backend/app/Http/Requests/AddToCartRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Debug: Log authorization check
        \Log::info('AddToCartRequest authorization check', [
            'user_authenticated' => auth()->check(),
            'user_role' => auth()->check() ? auth()->user()->role : 'no_user',
            'user_id' => auth()->check() ? auth()->user()->id : 'no_user'
        ]);

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
            'product_id' => [
                'required',
                'integer',
                'exists:products,id'
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
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
            'product_id.required' => 'Vui lòng chọn sản phẩm',
            'product_id.integer' => 'ID sản phẩm không hợp lệ',
            'product_id.exists' => 'Sản phẩm không tồn tại',
            
            'quantity.required' => 'Vui lòng nhập số lượng',
            'quantity.integer' => 'Số lượng phải là số nguyên',
            'quantity.min' => 'Số lượng phải ít nhất là 1',
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
            'product_id' => 'sản phẩm',
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
            if ($this->has('product_id')) {
                $product = Product::find($this->input('product_id'));
                
                // Debug: Log product check
                \Log::info('AddToCartRequest product validation', [
                    'product_id' => $this->input('product_id'),
                    'product_found' => $product ? true : false,
                    'product_published' => $product ? $product->published : 'no_product',
                    'product_stock' => $product ? $product->stock_qty : 'no_product',
                    'requested_quantity' => $this->input('quantity', 1),
                    'user_id' => auth()->id(),
                    'product_seller_id' => $product ? $product->seller_id : 'no_product'
                ]);
                
                if ($product) {
                    // Kiểm tra sản phẩm có được publish không
                    if (!$product->published) {
                        $validator->errors()->add(
                            'product_id',
                            'Sản phẩm này hiện không có sẵn'
                        );
                    }
                    
                    // Kiểm tra số lượng tồn kho
                    $requestedQuantity = $this->input('quantity', 1);
                    if ($product->stock_qty < $requestedQuantity) {
                        $validator->errors()->add(
                            'quantity',
                            "Chỉ còn {$product->stock_qty} sản phẩm trong kho"
                        );
                    }
                    
                    // Kiểm tra customer không thể mua sản phẩm của chính mình (nếu là seller)
                    if (auth()->check() && $product->seller_id === auth()->id()) {
                        $validator->errors()->add(
                            'product_id',
                            'Bạn không thể mua sản phẩm của chính mình'
                        );
                    }
                }
            }
        });
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        \Log::error('AddToCartRequest authorization failed', [
            'user_authenticated' => auth()->check(),
            'user_role' => auth()->check() ? auth()->user()->role : 'no_user',
            'user_id' => auth()->check() ? auth()->user()->id : 'no_user'
        ]);

        parent::failedAuthorization();
    }
}