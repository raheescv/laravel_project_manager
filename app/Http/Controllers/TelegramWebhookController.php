<?php

namespace App\Http\Controllers;

use App\Helpers\TelegramHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected $telegram;

    public function __construct(TelegramHelper $telegram)
    {
        $this->telegram = $telegram;
    }

    public function handle(Request $request)
    {
        try {
            $update = $request->all();
            $this->telegram->handleUpdate($update);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
