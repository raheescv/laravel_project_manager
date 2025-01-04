<?php

namespace App\Models;

use App\Actions\Product\Inventory\CreateAction as InventoryCreateAction;
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

    public static function selfCreateByProduct($product, $user_id, $current_branch = 1)
    {
        $barcode_type = cache('barcode_type', '');
        $branches = cache('branches', []);
        foreach ($branches as $branch) {
            $data['product_id'] = $product->id;
            $data['cost'] = $product->cost;
            $data['branch_id'] = $branch->id;
            if ($current_branch == $data['branch_id']) {
                $data['quantity'] = $product['quantity'] ?? 0;
            } else {
                $data['quantity'] = 0;
            }
            $data['remarks'] = null;
            switch ($barcode_type) {
                case 'product_wise':
                    $data['barcode'] = $product->barcode;
                    break;
                default:
                    $data['barcode'] = generateBarcode();
                    break;
            }
            if (! $data['barcode']) {
                $data['barcode'] = generateBarcode();
            }
            $data['batch'] = 'General';
            $data['created_by'] = $data['updated_by'] = $user_id;
            $response = (new InventoryCreateAction)->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
        }
    }
}
