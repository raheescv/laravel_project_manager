<?php

namespace App\Models;

use App\Actions\Product\Inventory\CreateAction as InventoryCreateAction;
use App\Jobs\BranchProductCreationJob;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\CurrentBranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Inventory extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity',
        'barcode',
        'batch',
        'cost',
        'model',
        'model_id',
        'remarks',
        'created_by',
        'updated_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'branch_id' => ['required'],
            'product_id' => ['required'],
            'quantity' => ['required'],
            'barcode' => ['required'],
            'batch' => ['required'],
            'cost' => ['required'],
            'created_by' => ['required'],
            'updated_by' => ['required'],
        ], $merge);
    }

    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());
    }

    public static function find($id)
    {
        return self::withoutGlobalScopes()->find($id);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeCurrentBranch($query)
    {
        return CurrentBranchScope::apply($query);
    }

    public static function selfCreateByProduct($product, $userId, $quantity = 0, $current_branch = 1)
    {
        $barcode_type = cache('barcode_type', '');
        $branches = cache('branches', []);
        foreach ($branches as $branch) {
            $data['product_id'] = $product->id;
            $data['cost'] = $product->cost;
            $data['branch_id'] = $branch->id;
            if ($current_branch == $data['branch_id']) {
                $data['quantity'] = $quantity;
            } else {
                $data['quantity'] = 0;
            }
            $data['remarks'] = null;
            switch ($barcode_type) {
                case 'product_wise':
                    $data['barcode'] = $product->barcode;
                    break;
            }
            if (! isset($data['barcode'])) {
                $data['barcode'] = generateBarcode();
            }
            $data['batch'] = 'General';
            $data['created_by'] = $data['updated_by'] = $userId;
            $response = (new InventoryCreateAction())->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            BranchProductCreationJob::dispatch(null, $userId, $product->id);
        }
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('products.name');
        $self = $self->join('products', 'inventories.product_id', '=', 'products.id');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value) {
                $value = trim($value);

                return $q->where('products.name', 'like', "%{$value}%")
                    ->orWhere('products.name_arabic', 'like', "%{$value}%")
                    ->orWhere('products.code', 'like', "%{$value}%")
                    ->orWhere('products.size', 'like', "%{$value}%")
                    ->orWhere('products.color', 'like', "%{$value}%")
                    ->orWhere('inventories.batch', 'like', "%{$value}%")
                    ->orWhere('inventories.cost', 'like', "%{$value}%")
                    ->orWhere('inventories.barcode', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['type'] ?? '', function ($query, $value) {
            return $query->where('products.type', $value);
        });
        $self = $self->when($request['branch_id'] ?? session('branch_id'), function ($query, $value) {
            return $query->where('inventories.branch_id', $value);
        });
        $self = $self->limit(10);
        $self = $self->get([
            'inventories.id',
            'inventories.product_id',
            'inventories.barcode',
            'inventories.batch',
            'inventories.quantity',
            'products.name',
            'products.code',
            'products.size',
            'products.mrp',
            'products.type',
        ])->toArray();
        $return['items'] = $self;

        return $return;
    }


    public static function getProductBySaleId($sale_id)
    {
        $saleProduct = \App\Models\SaleProduct::with('product')
            ->where('sale_id', $sale_id)
            ->first();

        if (!$saleProduct) {
            return null;
        }

        return [
            'id' => $saleProduct->product->id,
            'name' => $saleProduct->product->name,
            'barcode' => $saleProduct->product->barcode ?? '',
            'batch' => $saleProduct->batch ?? '',
            'size' => $saleProduct->product->size ?? '',
            'code' => $saleProduct->product->code ?? '',
            'color' => $saleProduct->product->color ?? '',
            'mrp' => $saleProduct->product->mrp ?? '',
            'image' => $saleProduct->product->image ?? cache('logo'),
            'type' => $saleProduct->product->type ?? 'product',
        ];
    }
}
