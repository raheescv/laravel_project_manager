<?php

use Illuminate\Support\Facades\File;

if (! function_exists('writeToEnv')) {
    function writeToEnv($key, $value)
    {
        $path = base_path('.env');
        if (File::exists($path)) {
            $envContents = File::get($path);
            $keyPattern = '/^' . preg_quote($key) . '=.*/m';
            if (preg_match($keyPattern, $envContents)) {
                $envContents = preg_replace($keyPattern, $key . '=' . $value, $envContents);
            } else {
                $envContents .= PHP_EOL . $key . '=' . $value;
            }
            File::put($path, $envContents);

            return true;
        }

        return false;
    }
}

if (! function_exists('resolutionOptions')) {
    function resolutionOptions()
    {
        return [
            '5S' => '5 seconds',
            '10S' => '10 seconds',
            '15S' => '15 seconds',
            '30S' => '30 seconds',
            '45S' => '45 seconds',
            '1' => '1 minute',
            '2' => '2 minute',
            '3' => '3 minute',
            '5' => '5 minute',
            '10' => '10 minute',
            '15' => '15 minute',
            '20' => '20 minute',
            '30' => '30 minute',
            '60' => '60 minute',
            '120' => '120 minute',
            '240' => '240 minute',
        ];
    }
}

if (! function_exists('orderStatus')) {
    function orderStatus($key)
    {
        $statues = [
            1 => "Canceled",
            2 => "Traded / Filled",
            3 => "(Not used currently)",
            4 => "Transit",
            5 => "Rejected",
            6 => "Pending",
            7 => "Expired",
        ];
        return $statues[$key];
    }
}

if (! function_exists('orderSegments')) {
    function orderSegments($key)
    {
        $statues = [
            10 => "E (Equity)",
            11 => "D (F&O)",
            12 => "C (Currency)",
            20 => "M (Commodity)",
        ];
        return $statues[$key];
    }
}

if (! function_exists('orderTypes')) {
    function orderTypes($key)
    {
        $statues = [
            1 => "Limit Order",
            2 => "Market Order",
            3 => "Stop Order (SL-M)",
            4 => "Stoplimit Order (SL-L)",
        ];
        return $statues[$key];
    }
}