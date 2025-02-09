<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'size',
        'type',
        'name',
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
}
