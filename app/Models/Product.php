<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_arabic',
        'unit_id',
        'department_id',
        'main_category_id',
        'sub_category_id',
        'hsn_code',
        'tax',
        'description',
        'cost',
        'mrp',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class)->ignore($id)],
        ], $merge);
    }
}
