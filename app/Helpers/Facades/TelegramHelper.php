<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class TelegramHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Helpers\TelegramHelper::class;
    }
}
