<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A choice picked on a sale line, kept as a snapshot (group and option names,
 * price, stock item) so a renamed or repriced option never rewrites an old
 * receipt. parent_id points at the pick that opened this one's group.
 */
class SaleItemOption extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sale_item_id',
        'parent_id',
        'option_group_id',
        'option_group_item_id',
        'group_name',
        'option_name',
        'price',
        'product_id',
        'stock_quantity',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'stock_quantity' => 'float',
            'sort' => 'integer',
        ];
    }

    /** @return BelongsTo<SaleItem, $this> */
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** "+ Red Bull", or "+ Chia (3.00)" when the pick costs extra — under the line on receipts and the sale view. */
    public function receiptLabel(): string
    {
        return '+ '.$this->option_name.($this->price ? ' ('.currency($this->price).')' : '');
    }

    /**
     * One pick as the tills and the mobile API show it under a sale line.
     *
     * @return array{group_name: string, option_name: string, price: float, nested: bool}
     */
    public function toLine(): array
    {
        return [
            'group_name' => (string) $this->group_name,
            'option_name' => (string) $this->option_name,
            'price' => (float) $this->price,
            'nested' => (bool) $this->parent_id,
        ];
    }
}
