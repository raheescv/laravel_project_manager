<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Meta WhatsApp Cloud API webhook (Meta App Dashboard → WhatsApp → Configuration).
 *
 * GET  is Meta's one-time subscription handshake: echo hub.challenge when the
 *      verify token matches.
 * POST carries message and status events, signed with the Meta app secret in
 *      the X-Hub-Signature-256 header.
 *
 * Both secrets are read per tenant from Configuration first (tenants share one
 * .env), falling back to config/services.php.
 */
class WhatsappWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $expectedToken = (string) $this->setting('whatsapp_webhook_verify_token', config('services.whatsapp_webhook.verify_token'));

        $isValidHandshake = $request->query('hub_mode') === 'subscribe'
            && $expectedToken !== ''
            && hash_equals($expectedToken, (string) $request->query('hub_verify_token'));

        if (! $isValidHandshake) {
            Log::warning('WhatsApp webhook verification failed', ['ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request): Response
    {
        if (! $this->hasValidSignature($request)) {
            Log::warning('WhatsApp webhook signature mismatch', ['ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        Log::info('WhatsApp webhook received', ['payload' => $request->json()->all()]);

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Meta signs the raw body with HMAC-SHA256 using the app secret. When no
     * secret is configured the check is skipped so the endpoint can be set up
     * before the secret is known.
     */
    private function hasValidSignature(Request $request): bool
    {
        $appSecret = (string) $this->setting('whatsapp_app_secret', config('services.whatsapp_webhook.app_secret'));

        if ($appSecret === '') {
            return true;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, (string) $request->header('X-Hub-Signature-256'));
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        $value = Configuration::where('key', $key)->value('value');

        return filled($value) ? $value : $default;
    }
}
