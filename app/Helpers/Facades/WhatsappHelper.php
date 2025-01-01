<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class WhatsappHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'whatsapp.helper';
    }
}
