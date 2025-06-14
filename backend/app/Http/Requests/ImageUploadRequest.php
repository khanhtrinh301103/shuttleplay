<?php
// File location: backend/app/Http/Requests/ImageUploadRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Chỉ cho phép seller upload ảnh
        return auth()->check() && auth()->user()->role === 'seller';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $maxFiles = config('cloudinary.max_files_per_product', 10);
        $maxFileSize = config('cloudinary.max_file_size', 10485760) / 1024; // Convert to KB
        $allowedFormats = implode(',', config('cloudinary.allowed_formats', ['jpeg', 'jpg', 'png', 'webp', 'gif']));

        return [
            'images' => [
                'required',
                'array',
                'min:1',
                "max:{$maxFiles}"
            ],
            'images.*' => [
                'required',
                'image',
                "mimes:{$allowedFormats}",
                "max:{$maxFileSize}"
            ],
            'main_image_index' => [
                'sometimes',
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
        $maxFiles = config('cloudinary.max_files_per_product', 10);
        $maxFileSize = round(config('cloudinary.max_file_size', 10485760) / 1024 / 1024, 2);
        $allowedFormats = implode(', ', config('cloudinary.allowed_formats', ['jpeg', 'jpg', 'png', 'webp', 'gif']));

        return [
            'images.required' => 'Vui lòng chọn ít nhất một ảnh',
            'images.array' => 'Dữ liệu ảnh không hợp lệ',
            'images.min' => 'Vui lòng chọn ít nhất một ảnh',
            'images.max' => "Tối đa {$maxFiles} ảnh cho mỗi sản phẩm",
            
            'images.*.required' => 'File ảnh không được để trống',
            'images.*.image' => 'File phải là ảnh',
            'images.*.mimes' => "Ảnh phải có định dạng: {$allowedFormats}",
            'images.*.max' => "Kích thước ảnh không được vượt quá {$maxFileSize}MB",
            
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
                $images = $this->file('images', []);
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