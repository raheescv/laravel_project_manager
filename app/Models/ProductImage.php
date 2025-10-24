<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'method',
        'degree',
        'sort_order',
        'path',
        'size',
        'type',
        'name',
    ];

    protected $casts = [
        'degree' => 'integer',
        'sort_order' => 'integer',
    ];

    public static function rules($merge = [])
    {
        return array_merge([
            'product_id' => ['required'],
            'path' => ['required'],
            'name' => ['required'],
        ], $merge);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get only normal images
     */
    public function scopeNormal($query)
    {
        return $query->where('method', 'normal');
    }

    /**
     * Scope to get only 360-degree images
     */
    public function scopeAngle($query)
    {
        return $query->where('method', 'angle');
    }

    /**
     * Scope to order 360-degree images by angle
     */
    public function scopeOrderedByAngle($query)
    {
        return $query->orderBy('sort_order')->orderBy('degree');
    }

    /**
     * Get the full URL for the image
     */
    public function getUrlAttribute(): string
    {
        return url($this->path);
    }
}
