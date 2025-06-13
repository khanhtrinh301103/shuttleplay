<?php
// File location: backend/app/Models/ProductImage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_images';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'image_url',
        'is_main',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_main' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the product that owns the image.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to only include main images.
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /**
     * Scope a query to only include non-main images.
     */
    public function scopeSecondary($query)
    {
        return $query->where('is_main', false);
    }

    /**
     * Check if this is the main image.
     */
    public function isMain(): bool
    {
        return $this->is_main;
    }

    /**
     * Get the image file name from URL.
     */
    public function getFileNameAttribute()
    {
        return basename($this->image_url);
    }

    /**
     * Get the image extension from URL.
     */
    public function getExtensionAttribute()
    {
        return pathinfo($this->image_url, PATHINFO_EXTENSION);
    }
}