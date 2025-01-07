<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity_in',
        'quantity_out',
        'balance',
        'barcode',
        'batch',
        'cost',

        'model',
        'model_id',
        'remarks',

        'user_id',
        'user_name',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'branch_id' => ['required'],
            'product_id' => ['required'],
            'quantity_in' => ['required'],
            'quantity_out' => ['required'],
            'balance' => ['required'],
            'barcode' => ['required'],
            'batch' => ['required'],
            'cost' => ['required'],
            'user_id' => ['required'],
        ], $merge);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
