<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'price_type',
        'amount',
        'start_date',
        'end_date',
        'status',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'product_id' => ['required'],
            'price_type' => ['required'],
            'amount' => ['required'],
        ], $merge);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeNormal($q)
    {
        return $q->where('price_type', 'normal')->active();
    }

    public function scopeOffer($q)
    {
        return $q->active()->where('price_type', 'offer')->where('start_date', '<=', date('Y-m-d'))->where('end_date', '>=', date('Y-m-d'));
    }

    public function scopeHomeService($q)
    {
        return $q->where('price_type', 'home_service')->active();
    }
}
