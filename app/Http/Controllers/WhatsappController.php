<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'qr' => 'required|string',
        ]);
        info($request->all());
        info($validated);
        info($validated['qr']);
        session(['whatsapp_qr_code' => $validated['qr']]);
        return response()->json(['success' => true]);
    }
}
