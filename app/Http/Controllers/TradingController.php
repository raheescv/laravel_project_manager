<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TradingController extends Controller
{
    public function index()
    {
        return view('trading.index');
    }

    public function fyersWebhook(Request $request)
    {
        if ($request->get('code') == '200') {
            writeToEnv('FYERS_AUTH_CODE', $request->get('auth_code'));

            return redirect(route('trading::index'));
        }
    }
}
