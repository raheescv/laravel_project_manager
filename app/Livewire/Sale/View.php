<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\UpdateAction;
use App\Helpers\Facades\SaleHelper;
use App\Helpers\Facades\WhatsappHelper;
use App\Models\Sale;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class View extends Component
{
    public $table_id;

    public $items = [];

    public $sale_return_items = [];

    public $payments = [];

    public $sale;

    public $sales = [];

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if ($this->table_id) {
            $this->sale = Sale::with('account:id,name', 'branch:id,name', 'items.product:id,name', 'items.employee:id,name', 'createdUser:id,name', 'updatedUser:id,name', 'cancelledUser:id,name', 'payments.paymentMethod:id,name')->find($this->table_id);
            if (! $this->sale) {
                return redirect()->route('sale::index');
            }
            $this->sales = $this->sale->toArray();
            $item_ids = [];
            $this->items = $this->sale->items->mapWithKeys(function ($item) use (&$item_ids) {
                $key = $item['employee_id'].'-'.$item['inventory_id'];
                $item_ids[] = $item['id'];

                return [
                    $key => [
                        'id' => $item['id'],
                        'key' => $key,
                        'employee_id' => $item['employee_id'],
                        'inventory_id' => $item['inventory_id'],
                        'product_id' => $item['product_id'],
                        'name' => $item['name'],
                        'employee_name' => $item['employee_name'],
                        'tax_amount' => $item['tax_amount'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => round($item['quantity'], 3),
                        'gross_amount' => $item['gross_amount'],
                        'discount' => $item['discount'],
                        'tax' => $item['tax'],
                        'total' => $item['total'],
                        'effective_total' => $item['effective_total'],
                        'created_by' => $item['created_by'],
                    ],
                ];
            })->toArray();
            $this->sale_return_items = SaleReturnItem::with('saleReturn:id,other_discount,total', 'product:id,name')->whereIn('sale_item_id', $item_ids)->get();
            $this->payments = $this->sale->payments->map->only(['id', 'amount', 'date', 'payment_method_id', 'created_by', 'name'])->toArray();
        }
    }

    public function save($type = 'completed')
    {
        try {
            $oldStatus = $this->sales['status'];
            DB::beginTransaction();
            if (! count($this->items)) {
                throw new \Exception('Please add any item', 1);
            }
            $this->sales['status'] = $type;
            $this->sales['items'] = $this->items;
            $this->sales['payments'] = $this->payments;

            $user_id = Auth::id();
            $response = (new UpdateAction())->execute($this->sales, $this->table_id, $user_id);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->mount($this->table_id);

            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
            $this->sales['status'] = $oldStatus;
        }
    }

    public function sendToWhatsapp($table_id = null)
    {
        if (! $table_id) {
            $table_id = $this->table_id;
        }
        $sale = Sale::find($table_id);
        if ($sale['customer_mobile']) {
            $number = $sale['customer_mobile'];
        } else {
            $number = $sale->account->mobile;
        }
        $imageContent = SaleHelper::saleInvoice($table_id, 'thermal');
        $image_path = SaleHelper::convertHtmlToImage($imageContent, $sale->invoice_no);
        if (! $number) {
            $this->dispatch('error', ['message' => 'Invalid Number']);

            goto skip;
        }
        $data = [
            'number' => $number,
            'message' => 'Please Check Your Invoice : '.currency($sale->grand_total),
            'filePath' => $image_path,
        ];
        $response = WhatsappHelper::send($data);
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);
        } else {
            $this->dispatch('success', ['message' => $response['message']]);
        }
        skip :
    }

    public function render()
    {
        return view('livewire.sale.view');
    }
}
