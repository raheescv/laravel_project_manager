<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchSymbols extends Command
{
    protected $signature = 'fyers:fetch-symbols';

    protected $description = 'Fetch and store Fyers trading symbols';

    public function handle()
    {
        $urls = [
            'NSE_EQ' => 'https://public.fyers.in/sym_details/NSE_EQ.csv',
            'NSE_FO' => 'https://public.fyers.in/sym_details/NSE_FO.csv',
            'BSE_EQ' => 'https://public.fyers.in/sym_details/BSE_EQ.csv',
        ];

        foreach ($urls as $market => $url) {
            $this->info("Fetching symbols from: $url");

            try {
                $response = Http::get($url);
                if ($response->successful()) {
                    Storage::put("symbols/{$market}.csv", $response->body());
                    $this->info("$market symbols stored successfully!");
                } else {
                    $this->error("Failed to fetch $market symbols.");
                }
            } catch (\Exception $e) {
                $this->error("Error fetching $market symbols: ".$e->getMessage());
            }
        }
    }
}
