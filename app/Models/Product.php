<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'name_arabic',
        'thumbnail',

        'unit_id',
        'department_id',
        'main_category_id',
        'sub_category_id',

        'hsn_code',
        'tax',

        'description',
        'is_selling',

        'cost',
        'mrp',

        'pattern',
        'color',
        'size',
        'model',
        'brand',
        'part_no',

        'min_stock',
        'max_stock',
        'location',
        'reorder_level',
        'plu',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class, 'name')->whereNull('deleted_at')->ignore($id)],
            'code' => ['required', Rule::unique(self::class, 'code')->whereNull('deleted_at')->ignore($id)],
            'unit_id' => ['required'],
            'department_id' => ['required'],
            'main_category_id' => ['required'],
            'sub_category_id' => ['required'],
            'cost' => ['required'],
            'mrp' => ['required'],
        ], $merge);
    }
}
