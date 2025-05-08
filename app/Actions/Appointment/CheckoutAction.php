<?php

namespace App\Actions\Appointment;

use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Models\Appointment;
use App\Models\Configuration;
use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;

class CheckoutAction
{
    public function execute($id, $userId)
    {
        try {
            if (Auth::user()->cannot('appointment.checkout')) {
                throw new \Exception('You do not have permission to checkout the appointment.', 1);
            }
            $model = Appointment::find($id);
            if (! $model) {
                throw new \Exception("Appointment not found with the specified ID: $id.", 1);
            }

            $saleData = [
                'branch_id' => $model->branch_id,
                'date' => date('Y-m-d'),
                'due_date' => date('Y-m-d'),
                'sale_type' => 'normal',
                'account_id' => $model->account_id,
                'tax_amount' => 0,
                'other_discount' => 0,
                'freight' => 0,
                'grand_total' => 0,
                'paid' => 0,
                'address' => null,
                'status' => 'completed',
                'created_by' => $userId,
                'updated_by' => $userId,
            ];

            $saleData['items'] = [];
            $saleData['payments'] = [];
            foreach ($model->items as $value) {
                $inventory_id = Inventory::where('branch_id', $model->branch_id)
                    ->where('product_id', $value->service_id)
                    ->value('id');

                $single = [
                    'inventory_id' => $inventory_id,
                    'employee_id' => $value->employee_id,
                    'product_id' => $value->service_id,
                    'unit_price' => $value->service->mrp,
                    'quantity' => 1,
                    'net_amount' => $value->service->mrp * 1,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $value->service->mrp * 1,
                ];
                $saleData['items'][] = $single;
            }
            $saleData['items'] = collect($saleData['items']);

            $saleData['gross_amount'] = $saleData['items']->sum('net_amount');
            $saleData['item_discount'] = $saleData['items']->sum('discount');
            $saleData['total_quantity'] = $saleData['items']->sum('quantity');
            $saleData['total'] = $saleData['items']->sum('total');

            $default_payment_method_id = Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1;
            $paymentSingle = [
                'payment_method_id' => $default_payment_method_id,
                'amount' => $saleData['total'],
            ];
            $saleData['payments'][] = $paymentSingle;

            $response = (new SaleCreateAction())->execute($saleData, $userId);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $data['updated_by'] = $userId;
            $data['status'] = 'completed';
            $data['sale_id'] = $response['data']['id'];

            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update Appointment';
            $return['data'] = $response['data'];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
