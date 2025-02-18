<?php

return [
    'whatsapp_server_url' => env('WHATSAPP_SERVER_URL'),
    'data_depth' => [
        'totalbuyqty' => 'Total buying quantity',
        'totalsellqty' => 'Total selling quantity',
        'bids' => 'Bidding price along with volume and total number of orders',
        'ask' => 'Offer price with volume and total number of orders',
        'o' => 'Price at market opening time',
        'h' => 'Highest price for the day',
        'l' => 'Lowest price for the day',
        'c' => 'Price at the of market closing',
        'chp' => "Percentage of change between the current value and the previous day's market close",
        'tick_Size' => 'Minimum price multiplier',
        'ch' => 'Change value',
        'ltq' => 'Last traded quantity',
        'ltt' => 'Last traded time',
        'ltp' => 'Last traded price',
        'v' => 'Volume traded',
        'atp' => 'Average traded price',
        'lower_ckt' => 'Lower circuit price',
        'upper_ckt' => 'upper circuit price',
        'expiry' => 'Expiry date',
        'oi' => 'Open interest',
        'oiflag' => 'bool	Boolean flag for OI data, true or false',
        'pdoi' => 'previous day open interest',
        'oipercent' => 'Change in open Interest percentage',
    ],
    'quote' => [
        'ch' => 'Change value',
        'chp' => "Percentage of change between the current value and the previous day's market close",
        'lp' => 'Last traded price',
        'spread' => 'Difference between lowest asking and highest bidding price',
        'ask' => 'Asking price for the symbol',
        'bid' => 'Bidding price for the symbol',
        'open_price' => 'Price at market opening time',
        'high_price' => 'Highest price for the day',
        'low_price' => 'Lowest price for the day',
        'prev_close_price' => 'Previous closing price',
        'volume' => 'Volume traded',
        'short_name' => 'Short name',
        'exchange' => 'Name of the exchange',
        'description' => 'Description of the symbol',
        'original_name' => 'Original name of the symbol name provided by the use',
        'symbol' => 'Symbol name provided by the user',
        'fyToken' => 'Unique token for each symbol',
        'tt' => 'Todays time',
    ],
];
