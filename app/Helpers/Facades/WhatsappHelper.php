<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Proxies to the configured WhatsApp driver through {@see \App\Helpers\WhatsappManager}.
 *
 * @method static string driverName()
 * @method static object helper()
 * @method static array send(array $data)
 * @method static array sendMessage(string $to, string $message, array $options = [])
 * @method static array sendTemplate(string $to, string $templateName, string $languageCode = 'en', array $components = [], array $options = [])
 * @method static array sendImage(string $to, string $imageUrl, ?string $caption = null)
 * @method static array sendTemplateWithImage(string $to, string $templateName, string $imageUrl, string $languageCode = 'en', ?string $footerText = null)
 * @method static array getCall(string $endpoint)
 * @method static array postCall(string $endpoint)
 * @method static array checkStatus()
 * @method static array getQr()
 * @method static array disconnect()
 *
 * @see \App\Helpers\WhatsappManager
 */
class WhatsappHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'whatsapp.helper';
    }
}
