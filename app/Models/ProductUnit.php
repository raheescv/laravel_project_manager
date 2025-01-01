<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'sub_unit_id',
        'conversion_factor',
        'barcode',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'product_id' => ['required'],
            'sub_unit_id' => ['required'],
            'conversion_factor' => ['required'],
            'barcode' => ['required'],
        ], $merge);
    }

    public function subUnit()
    {
        return $this->belongsTo(Unit::class, 'sub_unit_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
