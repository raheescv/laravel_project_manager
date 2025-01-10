<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\WhatsappProvider::class,
    App\Providers\SaleProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class,
    Spatie\Html\HtmlServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
    Milon\Barcode\BarcodeServiceProvider::class,
];
