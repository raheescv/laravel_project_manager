<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class SaleHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sale.helper';
    }
}
