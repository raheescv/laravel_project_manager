<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\HealthServiceProvider::class,
    // App\Providers\HorizonServiceProvider::class,
    App\Providers\OllamaProvider::class,
    App\Providers\OpenAIServiceProvider::class,
    App\Providers\SaleProvider::class,
    App\Providers\MoqSolutionsProvider::class,
    App\Providers\TelegramServiceProvider::class,
    App\Providers\WhatsappProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
    Milon\Barcode\BarcodeServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class,
    Spatie\Html\HtmlServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
];
