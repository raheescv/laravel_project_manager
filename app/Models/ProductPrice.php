<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'product_offer_id',
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

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductOffer, $this> */
    public function productOffer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class);
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
