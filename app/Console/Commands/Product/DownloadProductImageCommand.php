<?php

namespace App\Console\Commands\Product;

use App\Models\Product;
use App\Services\ProductImageGeminiService;
use Illuminate\Console\Command;

class DownloadProductImageCommand extends Command
{
    protected $signature = 'product:download-image
                            {product_id? : Product ID}
                            {--limit=10 : Bulk mode limit when product_id is not provided}
                            {--prompt= : Optional custom prompt}
                            {--set-thumbnail : Set generated image as thumbnail}';

    protected $description = 'Download product images with Gemini and attach them to products';

    public function __construct(
        protected ProductImageGeminiService $productImageGeminiService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! config('ai.providers.gemini.key')) {
            $this->error('GEMINI_API_KEY is missing. Please set it in your environment.');

            return self::FAILURE;
        }

        $productId = $this->argument('product_id');
        $prompt = $this->option('prompt') ?: null;
        $setThumbnail = (bool) $this->option('set-thumbnail');

        if ($productId) {
            $product = Product::find($productId);

            if (! $product) {
                $this->error("Product not found for ID: {$productId}");

                return self::FAILURE;
            }

            return $this->generateForProduct($product, $prompt, $setThumbnail);
        }

        $limit = max((int) $this->option('limit'), 1);
        $products = Product::query()
            ->where('type', 'product')
            ->whereNull('thumbnail')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($products->isEmpty()) {
            $this->info('No products found without thumbnail.');

            return self::SUCCESS;
        }

        $this->info('Processing '.$products->count().' products...');
        $successCount = 0;

        foreach ($products as $product) {
            $status = $this->generateForProduct($product, $prompt, $setThumbnail);

            if ($status === self::SUCCESS) {
                $successCount++;
            }
        }

        $this->newLine();
        $this->info("Completed. Success: {$successCount}, Failed: ".($products->count() - $successCount));

        return self::SUCCESS;
    }

    private function generateForProduct($product, ?string $prompt, bool $setThumbnail): int
    {
        $this->line("Generating image for Product #{$product->id} - {$product->name}");

        $response = $this->productImageGeminiService->generateAndAttach(
            $product,
            $prompt,
            $setThumbnail
        );

        if (! ($response['success'] ?? false)) {
            $this->error("Failed: {$response['message']}");

            return self::FAILURE;
        }

        $this->info('Saved: '.($response['path'] ?? 'N/A'));

        return self::SUCCESS;
    }
}

