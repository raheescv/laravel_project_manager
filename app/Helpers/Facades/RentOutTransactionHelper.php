<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class RentOutTransactionHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rent_out_transaction.helper';
    }
}
