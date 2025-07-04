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
        'is_favorite',

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

        'priority',
        'status',

        'created_by',
        'updated_by',
        'second_reference_no',
    ];

    public static function rules($data, $id = 0, $merge = [])
    {
        $rules = [
            'name' => [
                'required',
                'max:100',
                Rule::unique('products')->where('type', $data['type'])->whereNull('deleted_at')->ignore($id),
            ],
            'type' => ['required', 'max:100'],
            'code' => ['required'],
            'unit_id' => ['required'],
            'department_id' => ['required'],
            'main_category_id' => ['required'],
            'cost' => ['required'],
            'mrp' => ['required'],
        ];

        return array_merge($rules, $merge);
    }

    public function scopeService($query)
    {
        return $query->where('type', 'service');
    }

    public function scopeFavorite($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeProduct($query)
    {
        return $query->where('type', 'product');
    }

    public function scopeActive($query)
    {
        return $query->where('is_selling', true);
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
        $value['is_favorite'] = $value['is_favorite'] ?? true;
        $data['is_favorite'] = in_array($value['is_favorite'], ['Yes', true]) ? true : false;
        $value['is_selling'] = $value['is_selling'] ?? true;
        $data['is_selling'] = in_array($value['is_selling'], ['Yes', true]) ? true : false;
        $data['unit'] = $data['unit'] ?? 'Nos';
        $unit = Unit::firstOrCreate(['name' => $data['unit']], ['code' => $data['unit']]);
        $data['unit_id'] = $unit->id;
        $department_id = Department::selfCreate($data['department']);
        $data['department_id'] = $department_id;

        $mainCategoryData = [
            'name' => $data['main_category'],
        ];
        $main_category_id = Category::selfCreate($mainCategoryData);
        $data['main_category_id'] = $main_category_id;

        if (isset($data['sub_category']) && $data['sub_category'] !== '') {
            $subCategoryData = [
                'parent_id' => $data['main_category_id'],
                'name' => $data['sub_category'],
            ];
            $sub_category_id = Category::selfCreate($subCategoryData);
            $data['sub_category_id'] = $sub_category_id;
        }

        $data['created_by'] = $user_id;
        $data['updated_by'] = $user_id;

        return $data;
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value) {
                $value = trim($value);

                return $q->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
                    ->orWhere('barcode', 'like', "%{$value}%")
                    ->orWhere('color', 'like', "%{$value}%")
                    ->orWhere('size', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['type'] ?? '', function ($query, $value) {
            return $query->where('type', $value);
        });
        $self = $self->when($request['invoice_id'] ?? '', function ($query, $value) {
            return $query->whereIn('id', function ($subquery) use ($value) {
                $subquery->select('product_id')->from('purchase_items')->where('purchase_id', $value);
            });
        });
        $self = $self->limit(10);
        $self = $self->select(['name', 'barcode', 'code', 'size', 'mrp', 'cost', 'id', 'type']);
        $self = $self->when($request['invoice_id'] ?? '', function ($query, $value) {
            return $query->addSelect(['purchase_item_id' => function ($subquery) use ($value) {
                $subquery->select('id')->from('purchase_items')
                    ->whereColumn('product_id', 'products.id')
                    ->where('purchase_id', $value)
                    ->limit(1);
            }]);
        })->get()->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function getBrandDropDownList($request)
    {
        $self = self::orderBy('brand');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value) {
                $value = trim($value);

                return $q->where('brand', 'like', "%{$value}%");
            });
        });
        $self = $self->limit(10);
        $self = $self->distinct('brand');
        $self = $self->select(['brand']);
        $self = $self->get()->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_id');
    }

    public function saleTypePrice($type)
    {
        return match ($type) {
            'home_service' => $this->home_service_price ?: $this->mrp,
            'offer' => $this->offer_price ?: $this->mrp,
            default => $this->mrp,
        };
    }

    public function normalPrice()
    {
        return $this->prices()->normal()->latest()->first();
    }

    public function getHomeServicePriceAttribute()
    {
        return $this->prices()->homeService()->latest()->value('amount');
    }

    public function getOfferPriceAttribute()
    {
        return $this->prices()->offer()->latest()->value('amount');
    }
}
