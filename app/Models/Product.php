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
        'type',
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

        'time',

        'barcode',
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

        'status',

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

    public function scopeService($query)
    {
        return $query->where('type', 'service');
    }

    public function scopeProduct($query)
    {
        return $query->where('type', 'product');
    }

    public function scopeIsSelling($query)
    {
        return $query->where('is_selling', true);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
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

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public static function constructData($data, $user_id)
    {
        $value['is_selling'] = $value['is_selling'] ?? true;
        $data['is_selling'] = in_array($value['is_selling'], ['Yes', true]) ? true : false;
        $data['unit'] = $data['unit'] ?? 'Nos';
        $unit = Unit::firstOrCreate(['name' => $data['unit'], 'code' => $data['unit']]);
        $data['unit_id'] = $unit->id;
        $department_id = Department::selfCreate($data['department']);
        $data['department_id'] = $department_id;

        $mainCategoryData = [
            'name' => $data['main_category'],
        ];
        $main_category_id = Category::selfCreate($mainCategoryData);
        $data['main_category_id'] = $main_category_id;

        $subCategoryData = [
            'parent_id' => $data['main_category_id'],
            'name' => $data['sub_category'],
        ];
        $sub_category_id = Category::selfCreate($subCategoryData);
        $data['sub_category_id'] = $sub_category_id;

        $data['created_by'] = $user_id;
        $data['updated_by'] = $user_id;

        return $data;
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            $query->where(function ($q) use ($value) {
                $value = trim($value);
                $q->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
                    ->orWhere('barcode', 'like', "%{$value}%")
                    ->orWhere('color', 'like', "%{$value}%")
                    ->orWhere('size', 'like', "%{$value}%");
            });
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'barcode', 'size', 'mrp', 'cost', 'id', 'type'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
