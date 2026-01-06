<?php

namespace App\Console\Commands;

use App\Services\ShopifyService;
use Illuminate\Console\Command;

class TestShopifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-shopify-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shopify = new ShopifyService();

        // Get 10 products (default)
        $products = $shopify->getProducts();
        // Get 25 products
        $products = $shopify->getProducts(25);

        // Get products with additional parameters
        $products = $shopify->getProducts(10, ['page' => 2, 'fields' => 'id,title,price']);
    }
}
