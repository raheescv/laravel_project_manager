<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Account\CreateAction;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['account_type'] = 'asset';
            if (! $data['customer_type_id']) {
                unset($data['customer_type_id']);
            }
            $data['model'] = 'customer';
            $response = (new CreateAction())->execute($data);
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
        $mobile = $request->query('mobile');
        if (! $mobile) {
            return response()->json(['customers' => []]);
        }
        $customers = Account::where('mobile', 'like', '%'.$mobile.'%')
            ->where('model', 'customer')
            ->select('id', 'name', 'mobile', 'email')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'customers' => $customers,
        ]);
    }
}
