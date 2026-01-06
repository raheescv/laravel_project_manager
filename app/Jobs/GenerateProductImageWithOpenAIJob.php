<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateProductImageWithOpenAIJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $category,
        protected string $productName
    ) {}

    public function handle()
    {
        try {
            $apiKey = config('openai.api_key');
            if (! $apiKey || ! str_starts_with($apiKey, 'sk-') || strlen($apiKey) < 40) {
                throw new \Exception('Invalid OpenAI API key format. Please check your OPENAI_API_KEY configuration.');
            }

            // Build prompt based on product name and category
            $basePrompt = $this->buildPrompt($this->category, $this->productName);

            Log::info('Generating image with OpenAI DALL-E', [
                'prompt' => $basePrompt,
                'category' => $this->category,
                'productName' => $this->productName,
            ]);

            $startTime = time();

            // Call OpenAI DALL-E 3 API
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => 'dall-e-3',
                    'prompt' => $basePrompt,
                    'size' => '1024x1024',
                    'quality' => 'standard',
                    'n' => 1,
                ]);

            if (! $response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'prompt' => $basePrompt,
                ]);
                throw new \Exception('OpenAI API error: '.$errorMessage);
            }

            $responseData = $response->json();

            if (! isset($responseData['data'][0]['url'])) {
                throw new \Exception('No image URL in response: '.json_encode($responseData));
            }

            $imageUrl = $responseData['data'][0]['url'];
            $generationTime = time() - $startTime;

            Log::info('Image generation completed', [
                'generation_time' => $generationTime,
                'image_url' => $imageUrl,
            ]);

            return [
                'success' => true,
                'basePrompt' => $basePrompt,
                'image_path' => $imageUrl,
                'download_path' => $imageUrl,
                'image_url' => $imageUrl,
                'generation_time' => $generationTime,
            ];

        } catch (\Exception $e) {
            Log::error('Image generation failed', [
                'error' => $e->getMessage(),
                'category' => $this->category,
                'productName' => $this->productName,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate image: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Build a descriptive prompt for image generation based on product name and category
     */
    private function buildPrompt(string $category, string $productName): string
    {
        // Create a high-quality product photo prompt
        $prompt = "A high-quality, professional product photo of {$productName}";

        if ($category) {
            $prompt .= " from the {$category} category";
        }

        $prompt .= '. The product is displayed in a modern, elegant package or container, placed on a clean, minimal background with soft natural lighting. The packaging should feel premium, organic and fresh, nature-inspired. Professional product photography, studio lighting, high resolution, detailed, sharp focus';

        return $prompt;
    }
}
