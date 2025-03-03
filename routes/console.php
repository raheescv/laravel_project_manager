<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

$headers = [
    'token', 'symbol_name', 'lot_size', 'strike_price', 'premium', 'empty_field',
    'market_hours', 'expiry_date', 'timestamp', 'exchange_symbol', 'bid_qty', 'ask_qty',
    'trade_token', 'underlying', 'open_interest', 'strike_price_2', 'option_type',
    'fyToken', 'extra_field_1', 'extra_field_2', 'extra_field_3',
];
$contents = Storage::get('symbols/NSE_FO.csv');
$rows = explode("\n", trim($contents));
$csvArray = [];
foreach ($rows as $row) {
    $values = str_getcsv($row);
    $csvArray[] = array_combine($headers, $values);
}
$list = collect($csvArray)
    ->filter(function ($item) {
        return $item['underlying'] === 'NIFTY' && str_contains($item['symbol_name'], 'Mar 06');
    })
    ->unique('exchange_symbol');

// info('list count : '.count($list));

foreach ($list as $key => $value) {
    $no_data_symbols = cache('no_data_symbols', []);
    if (! in_array($value['exchange_symbol'], $no_data_symbols)) {
        Schedule::command('trade:intra-day '.$value['exchange_symbol'].' 75')->everyFiveMinutes();
    }
}

// $list = collect($csvArray)
//     ->filter(function ($item) {
//         return $item['underlying'] === 'BANKNIFTY' && str_contains($item['symbol_name'], 'Feb');
//     })
//     ->unique('exchange_symbol');
// foreach ($list as $key => $value) {
//     Schedule::command('trade:intra-day '.$value['exchange_symbol'].' 30')->everyFiveMinutes();
// }

Schedule::command('app:intra-day-selling')->everyMinute();
