<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Product extends Model implements AuditableContracts
{
    use Auditable;
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
        'created_by',
        'updated_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class, 'name')->whereNull('deleted_at')->ignore($id)],
            'code' => ['required'],
            'unit_id' => ['required'],
            'department_id' => ['required'],
            'main_category_id' => ['required'],
            'sub_category_id' => ['required'],
            'cost' => ['required'],
            'mrp' => ['required'],
        ], $merge);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function units()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function mainCategory()
    {
        return $this->belongsTo(Category::class, 'main_category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }
}
