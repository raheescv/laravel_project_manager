<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array generatePromptForServiceImage(string $category, string $service_name)
 * @method static array|null analyzeText(string $text)
 * @method static array|null paraphrasing($sentence, $style)
 * @method static mixed generateReport($data, $prompt)
 *
 * @see \App\Helpers\OllamaHelper
 */
class OllamaHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ollama.helper';
    }
}
