<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Account\CreateAction;
use App\Actions\Account\UpdateAction;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountCategory;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function store(Request $request, $id = null)
    {
        try {
            $data = $request->all();
            $data['account_type'] = 'asset';
            if (! $data['customer_type_id']) {
                unset($data['customer_type_id']);
            }
            $data['model'] = 'customer';

            $accountReceivableGroup = AccountCategory::firstWhere(['tenant_id' => 1, 'name' => 'Account Receivable']);
            if (! $accountReceivableGroup) {
                $data['account_category_id'] = $accountReceivableGroup['id'];
            }

            if ($id) {
                $response = (new UpdateAction())->execute($data, $id);
            } else {
                $response = (new CreateAction())->execute($data);
            }

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            $return['success'] = true;
            $return['customer'] = $response['data'];
            $return['message'] = $response['message'];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return response()->json($return);
    }

    public function get(Request $request)
    {
        $search = $request->query('search');
        $customers = Account::customer()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%');
            })
            ->select('id', 'name', 'mobile', 'email')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'customers' => $customers,
        ]);
    }
}
