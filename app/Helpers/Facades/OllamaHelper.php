<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class OllamaHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ollama.helper';
    }
}
