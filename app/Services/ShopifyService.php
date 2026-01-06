<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected string $storeUrl;

    protected string $accessToken;

    protected string $apiVersion;

    protected string $baseUrl;

    public $http;

    public function __construct()
    {
        $this->storeUrl = config('services.shopify.store_url', '');
        $this->accessToken = config('services.shopify.access_token', '');
        $this->apiVersion = config('services.shopify.api_version', '2024-10');

        // Build base URL: https://yourstore.myshopify.com/admin/api/2024-10
        $this->baseUrl = rtrim($this->storeUrl, '/').'/admin/api/'.$this->apiVersion;
        $headers = [
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $this->http = Http::withHeaders($headers);
    }

    /**
     * Get products from Shopify store
     *
     * @param  int  $limit  Number of products to retrieve (default: 10)
     * @param  array  $params  Additional query parameters (e.g., ['page' => 1, 'fields' => 'id,title'])
     *
     * @throws Exception
     */
    public function getProducts(int $limit = 10, array $params = []): array
    {
        try {
            // Build query parameters
            $queryParams = array_merge(
                [
                    'limit' => $limit,
                ],
                $params,
            );

            $url = $this->baseUrl.'/products.json';
            $response = $this->http->get($url, $queryParams);

            if (! $response->successful()) {
                Log::error('Shopify API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new Exception('Shopify API request failed: '.$response->body(), $response->status());
            }

            $data = $response->json();

            // Shopify returns products in a 'products' key
            return $data['products'] ?? [];
        } catch (Exception $e) {
            Log::error('Error fetching Shopify products', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get a single product by ID
     *
     * @param  int|string  $productId
     *
     * @throws Exception
     */
    public function getProduct($productId): array
    {
        try {
            $url = $this->baseUrl.'/products/'.$productId.'.json';

            $response = $this->http->get($url);

            if (! $response->successful()) {
                Log::error('Shopify API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new Exception('Shopify API request failed: '.$response->body(), $response->status());
            }

            $data = $response->json();

            return $data['product'] ?? [];
        } catch (Exception $e) {
            Log::error('Error fetching Shopify product', [
                'product_id' => $productId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get products count
     *
     * @throws Exception
     */
    public function getProductsCount(): int
    {
        try {
            $url = $this->baseUrl.'/products/count.json';

            $response = $this->http->get($url);

            if (! $response->successful()) {
                throw new Exception('Shopify API request failed: '.$response->body(), $response->status());
            }

            $data = $response->json();

            return $data['count'] ?? 0;
        } catch (Exception $e) {
            Log::error('Error fetching Shopify products count', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
